<?php

namespace App\Services\Purchasing;

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseReceiptStatus;
use App\Enums\PurchaseReturnStatus;
use App\Enums\StockMovementType;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptLine;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnLine;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\StockLedgerService;
use App\Services\Sales\SalesDocumentEventLogger;
use App\Support\Inventory\MovementCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseReceiptWorkflowService
{
    public function __construct(
        protected StockLedgerService $ledger,
        protected SalesDocumentEventLogger $events,
    ) {}

    /**
     * Explicit partial (or full) goods receipt against a purchase order.
     *
     * @param  list<array{purchase_order_line_id: int, quantity: float|int|string}>  $lineQuantities
     */
    public function receive(
        PurchaseOrder $order,
        User $user,
        array $lineQuantities,
        ?string $notes = null,
        ?string $idempotencyKey = null,
    ): PurchaseReceipt {
        abort_unless($user->can('purchase-orders.receive'), 403);

        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $existing = PurchaseReceipt::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing !== null) {
                return $existing->load(['lines', 'purchaseOrder.lines', 'warehouse']);
            }
        }

        return DB::transaction(function () use ($order, $user, $lineQuantities, $notes, $idempotencyKey) {
            if ($idempotencyKey !== null && $idempotencyKey !== '') {
                $existingInside = PurchaseReceipt::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();

                if ($existingInside !== null) {
                    return $existingInside->load(['lines', 'purchaseOrder.lines', 'warehouse']);
                }
            }

            $locked = PurchaseOrder::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->with('lines')
                ->firstOrFail();

            if (! $locked->status->canReceive()) {
                throw ValidationException::withMessages([
                    'purchase_order' => [__('scf.purchase_workflow.order_not_receivable')],
                ]);
            }

            $ledgerEnabled = (bool) config('inventory.ledger_enabled', false);
            $warehouse = $this->resolveWarehouseForLedger($locked, $ledgerEnabled);

            $normalized = $this->normalizeReceiveLines($locked, $lineQuantities);

            $receipt = PurchaseReceipt::query()->create([
                'reference_number' => $this->nextReceiptReference($locked),
                'purchase_order_id' => $locked->id,
                'warehouse_id' => $warehouse?->id ?? $locked->warehouse_id,
                'status' => PurchaseReceiptStatus::Posted,
                'received_at' => now(),
                'received_by' => $user->id,
                'idempotency_key' => ($idempotencyKey !== null && $idempotencyKey !== '') ? $idempotencyKey : null,
                'notes' => $notes,
            ]);

            foreach ($normalized as $row) {
                /** @var PurchaseOrderLine $poLine */
                $poLine = $row['line'];
                $qty = $row['quantity'];

                $receiptLine = PurchaseReceiptLine::query()->create([
                    'purchase_receipt_id' => $receipt->id,
                    'purchase_order_line_id' => $poLine->id,
                    'product_id' => $poLine->product_id,
                    'quantity' => $qty,
                ]);

                $poLine->update([
                    'quantity_received' => (float) $poLine->quantity_received + $qty,
                ]);

                if ($ledgerEnabled) {
                    $this->postReceiptLedger(
                        receipt: $receipt,
                        receiptLine: $receiptLine,
                        poLine: $poLine->fresh() ?? $poLine,
                        warehouse: $warehouse,
                        quantity: $qty,
                        user: $user,
                    );
                }
            }

            $fromStatus = $locked->status->value;
            $this->refreshReceiveStatus($locked);
            $fresh = $locked->fresh() ?? $locked;

            $this->events->log(
                $fresh,
                'received',
                $user,
                $fromStatus,
                $fresh->status->value,
                $notes,
                null,
                $receipt,
            );

            return $receipt->fresh(['lines', 'purchaseOrder.lines', 'warehouse']) ?? $receipt;
        });
    }

    /**
     * Explicit purchase return against previously received quantities.
     *
     * @param  list<array{purchase_order_line_id: int, quantity: float|int|string}>  $lineQuantities
     */
    public function returnGoods(
        PurchaseOrder $order,
        User $user,
        array $lineQuantities,
        ?string $notes = null,
        ?string $idempotencyKey = null,
    ): PurchaseReturn {
        abort_unless($user->can('purchase-orders.return'), 403);

        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $existing = PurchaseReturn::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing !== null) {
                return $existing->load(['lines', 'purchaseOrder.lines', 'warehouse']);
            }
        }

        return DB::transaction(function () use ($order, $user, $lineQuantities, $notes, $idempotencyKey) {
            if ($idempotencyKey !== null && $idempotencyKey !== '') {
                $existingInside = PurchaseReturn::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();

                if ($existingInside !== null) {
                    return $existingInside->load(['lines', 'purchaseOrder.lines', 'warehouse']);
                }
            }

            $locked = PurchaseOrder::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->with('lines')
                ->firstOrFail();

            if (! $locked->status->canReturn()) {
                throw ValidationException::withMessages([
                    'purchase_order' => [__('scf.purchase_workflow.order_not_returnable')],
                ]);
            }

            $ledgerEnabled = (bool) config('inventory.ledger_enabled', false);
            $warehouse = $this->resolveWarehouseForLedger($locked, $ledgerEnabled);

            $normalized = $this->normalizeReturnLines($locked, $lineQuantities);

            $return = PurchaseReturn::query()->create([
                'reference_number' => $this->nextReturnReference($locked),
                'purchase_order_id' => $locked->id,
                'warehouse_id' => $warehouse?->id ?? $locked->warehouse_id,
                'status' => PurchaseReturnStatus::Posted,
                'returned_at' => now(),
                'returned_by' => $user->id,
                'idempotency_key' => ($idempotencyKey !== null && $idempotencyKey !== '') ? $idempotencyKey : null,
                'notes' => $notes,
            ]);

            foreach ($normalized as $row) {
                /** @var PurchaseOrderLine $poLine */
                $poLine = $row['line'];
                $qty = $row['quantity'];

                $returnLine = PurchaseReturnLine::query()->create([
                    'purchase_return_id' => $return->id,
                    'purchase_order_line_id' => $poLine->id,
                    'product_id' => $poLine->product_id,
                    'quantity' => $qty,
                ]);

                $poLine->update([
                    'quantity_returned' => (float) $poLine->quantity_returned + $qty,
                ]);

                if ($ledgerEnabled) {
                    $this->postReturnLedger(
                        returnDoc: $return,
                        returnLine: $returnLine,
                        poLine: $poLine->fresh() ?? $poLine,
                        warehouse: $warehouse,
                        quantity: $qty,
                        user: $user,
                    );
                }
            }

            $this->events->log(
                $locked,
                'returned',
                $user,
                $locked->status->value,
                $locked->status->value,
                $notes,
                null,
                $return,
            );

            return $return->fresh(['lines', 'purchaseOrder.lines', 'warehouse']) ?? $return;
        });
    }

    public function refreshReceiveStatus(PurchaseOrder $order): PurchaseOrder
    {
        $order->load('lines');

        $totalQty = $order->lines->sum(fn (PurchaseOrderLine $l) => (float) $l->quantity);
        $receivedQty = $order->lines->sum(fn (PurchaseOrderLine $l) => (float) $l->quantity_received);

        if ($totalQty <= 0 || $receivedQty <= 0) {
            return $order;
        }

        // Do not overwrite billing terminal statuses.
        if (in_array($order->status, [
            PurchaseOrderStatus::PartiallyBilled,
            PurchaseOrderStatus::FullyBilled,
            PurchaseOrderStatus::Cancelled,
        ], true)) {
            return $order;
        }

        if ($receivedQty + 0.0001 >= $totalQty && $order->status === PurchaseOrderStatus::Confirmed) {
            $order->update(['status' => PurchaseOrderStatus::Received]);
        }

        return $order->fresh() ?? $order;
    }

    /**
     * @param  list<array{purchase_order_line_id: int, quantity: float|int|string}>  $lineQuantities
     * @return list<array{line: PurchaseOrderLine, quantity: float}>
     */
    protected function normalizeReceiveLines(PurchaseOrder $order, array $lineQuantities): array
    {
        if ($lineQuantities === []) {
            throw ValidationException::withMessages([
                'lines' => [__('scf.purchase_workflow.nothing_to_receive')],
            ]);
        }

        $linesById = $order->lines->keyBy('id');
        $normalized = [];

        foreach ($lineQuantities as $index => $row) {
            $lineId = (int) ($row['purchase_order_line_id'] ?? 0);
            $qty = $this->assertPositiveWholeQuantity($row['quantity'] ?? null, "lines.$index.quantity");

            $poLine = $linesById->get($lineId);
            if ($poLine === null) {
                throw ValidationException::withMessages([
                    "lines.$index.purchase_order_line_id" => [__('scf.purchase_workflow.invalid_receive_line')],
                ]);
            }

            $poLine = PurchaseOrderLine::query()->whereKey($poLine->id)->lockForUpdate()->firstOrFail();

            if ($qty > $poLine->quantityRemainingToReceive() + 0.0001) {
                throw ValidationException::withMessages([
                    "lines.$index.quantity" => [__('scf.purchase_workflow.over_receiving')],
                ]);
            }

            if ($poLine->product_id === null) {
                throw ValidationException::withMessages([
                    "lines.$index.purchase_order_line_id" => [__('scf.purchase_workflow.receive_product_required')],
                ]);
            }

            $product = Product::query()->find($poLine->product_id);
            if ($product === null || ! $product->is_active) {
                throw ValidationException::withMessages([
                    "lines.$index.purchase_order_line_id" => [__('scf.purchase_workflow.receive_product_inactive')],
                ]);
            }

            $normalized[] = ['line' => $poLine, 'quantity' => $qty];
        }

        return $normalized;
    }

    /**
     * @param  list<array{purchase_order_line_id: int, quantity: float|int|string}>  $lineQuantities
     * @return list<array{line: PurchaseOrderLine, quantity: float}>
     */
    protected function normalizeReturnLines(PurchaseOrder $order, array $lineQuantities): array
    {
        if ($lineQuantities === []) {
            throw ValidationException::withMessages([
                'lines' => [__('scf.purchase_workflow.nothing_to_return')],
            ]);
        }

        $linesById = $order->lines->keyBy('id');
        $normalized = [];

        foreach ($lineQuantities as $index => $row) {
            $lineId = (int) ($row['purchase_order_line_id'] ?? 0);
            $qty = $this->assertPositiveWholeQuantity($row['quantity'] ?? null, "lines.$index.quantity");

            $poLine = $linesById->get($lineId);
            if ($poLine === null) {
                throw ValidationException::withMessages([
                    "lines.$index.purchase_order_line_id" => [__('scf.purchase_workflow.invalid_return_line')],
                ]);
            }

            $poLine = PurchaseOrderLine::query()->whereKey($poLine->id)->lockForUpdate()->firstOrFail();

            if ($qty > $poLine->quantityRemainingToReturn() + 0.0001) {
                throw ValidationException::withMessages([
                    "lines.$index.quantity" => [__('scf.purchase_workflow.over_returning')],
                ]);
            }

            if ($poLine->product_id === null) {
                throw ValidationException::withMessages([
                    "lines.$index.purchase_order_line_id" => [__('scf.purchase_workflow.return_product_required')],
                ]);
            }

            $normalized[] = ['line' => $poLine, 'quantity' => $qty];
        }

        return $normalized;
    }

    protected function assertPositiveWholeQuantity(mixed $value, string $key): float
    {
        if ($value === null || $value === '') {
            throw ValidationException::withMessages([
                $key => [__('scf.purchase_workflow.invalid_receive_quantity')],
            ]);
        }

        $qty = (float) $value;

        if ($qty <= 0) {
            throw ValidationException::withMessages([
                $key => [__('scf.purchase_workflow.invalid_receive_quantity')],
            ]);
        }

        if (abs($qty - round($qty)) > 0.0001) {
            throw ValidationException::withMessages([
                $key => [__('scf.purchase_workflow.receive_quantity_must_be_whole')],
            ]);
        }

        return (float) (int) round($qty);
    }

    protected function resolveWarehouseForLedger(PurchaseOrder $order, bool $ledgerEnabled): ?Warehouse
    {
        if (! $ledgerEnabled) {
            if ($order->warehouse_id === null) {
                return null;
            }

            return Warehouse::query()->find($order->warehouse_id);
        }

        if ($order->warehouse_id === null) {
            throw ValidationException::withMessages([
                'warehouse_id' => [__('scf.purchase_workflow.warehouse_required_for_ledger')],
            ]);
        }

        $warehouse = Warehouse::query()->find($order->warehouse_id);

        if ($warehouse === null) {
            throw ValidationException::withMessages([
                'warehouse_id' => [__('scf.purchase_workflow.warehouse_required_for_ledger')],
            ]);
        }

        if (! $warehouse->is_active) {
            throw ValidationException::withMessages([
                'warehouse_id' => [__('scf.purchase_workflow.warehouse_inactive')],
            ]);
        }

        return $warehouse;
    }

    protected function postReceiptLedger(
        PurchaseReceipt $receipt,
        PurchaseReceiptLine $receiptLine,
        PurchaseOrderLine $poLine,
        ?Warehouse $warehouse,
        float $quantity,
        User $user,
    ): void {
        if ($warehouse === null || $poLine->product_id === null) {
            throw ValidationException::withMessages([
                'warehouse_id' => [__('scf.purchase_workflow.warehouse_required_for_ledger')],
            ]);
        }

        $this->ledger->post(MovementCommand::fromArray([
            'warehouse_id' => $warehouse->id,
            'product_id' => (int) $poLine->product_id,
            'variant_id' => null,
            'quantity' => (int) $quantity,
            'reserved_delta' => 0,
            'movement_type' => StockMovementType::PurchaseReceipt,
            'reason_code' => 'purchase_receipt',
            'idempotency_key' => sprintf('purchase_receipt:%d:line:%d', $receipt->id, $receiptLine->id),
            'occurred_at' => $receipt->received_at ?? now(),
            'reference_type' => PurchaseReceipt::class,
            'reference_id' => $receipt->id,
            'reference_line_id' => $receiptLine->id,
            'unit_cost' => $poLine->unit_price,
            'created_by' => $user->id,
            'notes' => $receipt->reference_number,
        ]));
    }

    protected function postReturnLedger(
        PurchaseReturn $returnDoc,
        PurchaseReturnLine $returnLine,
        PurchaseOrderLine $poLine,
        ?Warehouse $warehouse,
        float $quantity,
        User $user,
    ): void {
        if ($warehouse === null || $poLine->product_id === null) {
            throw ValidationException::withMessages([
                'warehouse_id' => [__('scf.purchase_workflow.warehouse_required_for_ledger')],
            ]);
        }

        try {
            $this->ledger->post(MovementCommand::fromArray([
                'warehouse_id' => $warehouse->id,
                'product_id' => (int) $poLine->product_id,
                'variant_id' => null,
                'quantity' => -1 * (int) $quantity,
                'reserved_delta' => 0,
                'movement_type' => StockMovementType::PurchaseReturn,
                'reason_code' => 'purchase_return',
                'idempotency_key' => sprintf('purchase_return:%d:line:%d', $returnDoc->id, $returnLine->id),
                'occurred_at' => $returnDoc->returned_at ?? now(),
                'reference_type' => PurchaseReturn::class,
                'reference_id' => $returnDoc->id,
                'reference_line_id' => $returnLine->id,
                'unit_cost' => $poLine->unit_price,
                'created_by' => $user->id,
                'notes' => $returnDoc->reference_number,
            ]));
        } catch (InsufficientStockException $exception) {
            throw ValidationException::withMessages([
                'quantity' => [__('scf.purchase_workflow.insufficient_stock_for_return')],
            ]);
        }
    }

    protected function nextReceiptReference(PurchaseOrder $order): string
    {
        return 'GRN-'.$order->reference_number.'-'.now()->format('ymdHis').'-'.Str::upper(Str::random(4));
    }

    protected function nextReturnReference(PurchaseOrder $order): string
    {
        return 'PRTN-'.$order->reference_number.'-'.now()->format('ymdHis').'-'.Str::upper(Str::random(4));
    }
}
