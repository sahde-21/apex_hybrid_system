<?php

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Enums\StockTransferStatus;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Variant;
use App\Models\Warehouse;
use App\Support\Inventory\MovementCommand;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockTransferWorkflowService
{
    public function __construct(
        protected StockLedgerService $ledger,
    ) {}

    /**
     * @param  array{
     *     reference_number: string,
     *     product_id: int,
     *     variant_id?: int|null,
     *     from_warehouse_id: int,
     *     to_warehouse_id: int,
     *     quantity: int,
     *     transfer_date: string,
     *     notes?: string|null
     * }  $data
     */
    public function createDraft(array $data, User $user): StockTransfer
    {
        $this->assertDraftPayload($data);

        return StockTransfer::query()->create([
            ...$data,
            'status' => StockTransferStatus::Draft,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDraft(StockTransfer $transfer, array $data, User $user): StockTransfer
    {
        $transfer = $this->lock($transfer);

        if (! $transfer->status->isEditable()) {
            throw new InvalidArgumentException(__('Only draft transfers can be edited.'));
        }

        $payload = array_merge([
            'reference_number' => $transfer->reference_number,
            'product_id' => $transfer->product_id,
            'variant_id' => $transfer->variant_id,
            'from_warehouse_id' => $transfer->from_warehouse_id,
            'to_warehouse_id' => $transfer->to_warehouse_id,
            'quantity' => $transfer->quantity,
            'transfer_date' => $transfer->transfer_date->toDateString(),
            'notes' => $transfer->notes,
        ], $data);

        unset($payload['status']);

        $this->assertDraftPayload($payload);

        $transfer->update([
            ...$payload,
            'updated_by' => $user->id,
        ]);

        return $transfer->fresh() ?? $transfer;
    }

    public function approve(StockTransfer $transfer, User $user): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $user) {
            $transfer = $this->lock($transfer);

            if (! $transfer->status->canApprove()) {
                throw new InvalidArgumentException(__('Transfer cannot be approved in its current status.'));
            }

            $this->assertReadyForTransition($transfer);

            $transfer->update([
                'status' => StockTransferStatus::Pending,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'updated_by' => $user->id,
            ]);

            return $transfer->fresh() ?? $transfer;
        });
    }

    public function ship(StockTransfer $transfer, User $user): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $user) {
            $transfer = $this->lock($transfer);

            if (! $transfer->status->canShip()) {
                throw new InvalidArgumentException(__('Transfer must be approved before shipping.'));
            }

            $this->assertReadyForTransition($transfer);

            $ledgerEnabled = (bool) config('inventory.ledger_enabled', false);

            if ($ledgerEnabled) {
                try {
                    $this->ledger->post(MovementCommand::fromArray([
                        'warehouse_id' => $transfer->from_warehouse_id,
                        'product_id' => $transfer->product_id,
                        'variant_id' => $transfer->variant_id,
                        'quantity' => -1 * abs($transfer->quantity),
                        'reserved_delta' => 0,
                        'movement_type' => StockMovementType::TransferShip,
                        'reason_code' => 'transfer_ship',
                        'idempotency_key' => sprintf('transfer:%d:ship', $transfer->id),
                        'occurred_at' => $transfer->transfer_date->startOfDay(),
                        'reference_type' => StockTransfer::class,
                        'reference_id' => $transfer->id,
                        'created_by' => $user->id,
                        'notes' => $transfer->notes ?? $transfer->reference_number,
                    ]));
                } catch (InsufficientStockException $exception) {
                    throw new InvalidArgumentException(
                        __('Insufficient stock at source warehouse to ship this transfer.'),
                        0,
                        $exception,
                    );
                }
            }

            $transfer->update([
                'status' => StockTransferStatus::InTransit,
                'shipped_by' => $user->id,
                'shipped_at' => now(),
                'updated_by' => $user->id,
            ]);

            return $transfer->fresh() ?? $transfer;
        });
    }

    public function receive(StockTransfer $transfer, User $user): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $user) {
            $transfer = $this->lock($transfer);

            if (! $transfer->status->canReceive()) {
                throw new InvalidArgumentException(__('Transfer must be in transit before receiving.'));
            }

            $this->assertReadyForTransition($transfer);

            $ledgerEnabled = (bool) config('inventory.ledger_enabled', false);

            if ($ledgerEnabled) {
                $this->ledger->post(MovementCommand::fromArray([
                    'warehouse_id' => $transfer->to_warehouse_id,
                    'product_id' => $transfer->product_id,
                    'variant_id' => $transfer->variant_id,
                    'quantity' => abs($transfer->quantity),
                    'reserved_delta' => 0,
                    'movement_type' => StockMovementType::TransferReceive,
                    'reason_code' => 'transfer_receive',
                    'idempotency_key' => sprintf('transfer:%d:receive', $transfer->id),
                    'occurred_at' => now(),
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'created_by' => $user->id,
                    'notes' => $transfer->notes ?? $transfer->reference_number,
                ]));
            }

            $transfer->update([
                'status' => StockTransferStatus::Completed,
                'received_by' => $user->id,
                'received_at' => now(),
                'updated_by' => $user->id,
            ]);

            return $transfer->fresh() ?? $transfer;
        });
    }

    public function cancel(StockTransfer $transfer, User $user): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $user) {
            $transfer = $this->lock($transfer);

            if (! $transfer->status->canCancel()) {
                throw new InvalidArgumentException(__('Transfer cannot be cancelled after shipment.'));
            }

            $transfer->update([
                'status' => StockTransferStatus::Cancelled,
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
                'updated_by' => $user->id,
            ]);

            return $transfer->fresh() ?? $transfer;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertDraftPayload(array $data): void
    {
        $qty = (int) ($data['quantity'] ?? 0);
        if ($qty <= 0) {
            throw new InvalidArgumentException(__('Transfer quantity must be greater than zero.'));
        }

        $fromId = (int) ($data['from_warehouse_id'] ?? 0);
        $toId = (int) ($data['to_warehouse_id'] ?? 0);

        if ($fromId === 0 || $toId === 0) {
            throw new InvalidArgumentException(__('Source and destination warehouses are required.'));
        }

        if ($fromId === $toId) {
            throw new InvalidArgumentException(__('Source and destination warehouses must be different.'));
        }

        $from = Warehouse::query()->whereKey($fromId)->first();
        $to = Warehouse::query()->whereKey($toId)->first();

        if ($from === null || ! $from->is_active || $to === null || ! $to->is_active) {
            throw new InvalidArgumentException(__('Both warehouses must be active.'));
        }

        $product = Product::query()->whereKey($data['product_id'] ?? null)->first();
        if ($product === null || ! $product->is_active) {
            throw new InvalidArgumentException(__('Product must be active.'));
        }

        $variantId = $data['variant_id'] ?? null;
        if ($variantId !== null) {
            $variant = Variant::query()->whereKey($variantId)->first();
            if ($variant === null || (int) $variant->product_id !== (int) $product->id) {
                throw new InvalidArgumentException(__('Variant must belong to the selected product.'));
            }
            if (! $variant->is_active) {
                throw new InvalidArgumentException(__('Variant must be active.'));
            }
        }
    }

    protected function assertReadyForTransition(StockTransfer $transfer): void
    {
        $this->assertDraftPayload([
            'product_id' => $transfer->product_id,
            'variant_id' => $transfer->variant_id,
            'from_warehouse_id' => $transfer->from_warehouse_id,
            'to_warehouse_id' => $transfer->to_warehouse_id,
            'quantity' => $transfer->quantity,
        ]);
    }

    protected function lock(StockTransfer $transfer): StockTransfer
    {
        return StockTransfer::query()->whereKey($transfer->id)->lockForUpdate()->firstOrFail();
    }
}
