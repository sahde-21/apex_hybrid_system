<?php

namespace App\Services\Inventory;

use App\Enums\InventoryAdjustmentReason;
use App\Enums\InventoryAdjustmentStatus;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Models\InventoryAdjustment;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use App\Models\Warehouse;
use App\Support\Inventory\MovementCommand;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryAdjustmentWorkflowService
{
    public function __construct(
        protected StockLedgerService $ledger,
    ) {}

    /**
     * @param  array{
     *     reference_number: string,
     *     product_id: int,
     *     variant_id?: int|null,
     *     warehouse_id: int,
     *     adjustment_date: string,
     *     quantity_change: int,
     *     reason: string,
     *     notes?: string|null
     * }  $data
     */
    public function createDraft(array $data, User $user): InventoryAdjustment
    {
        $this->assertDraftPayload($data);

        return InventoryAdjustment::query()->create([
            ...$data,
            'status' => InventoryAdjustmentStatus::Draft,
            'created_by' => $user->id,
        ]);
    }

    /**
     * @param  array{
     *     reference_number?: string,
     *     product_id?: int,
     *     variant_id?: int|null,
     *     warehouse_id?: int|null,
     *     adjustment_date?: string,
     *     quantity_change?: int,
     *     reason?: string,
     *     notes?: string|null
     * }  $data
     */
    public function updateDraft(InventoryAdjustment $adjustment, array $data, User $user): InventoryAdjustment
    {
        $adjustment = $this->lock($adjustment);

        if (! $adjustment->status->isEditable()) {
            throw new InvalidArgumentException(__('Only draft adjustments can be edited.'));
        }

        $payload = array_merge([
            'reference_number' => $adjustment->reference_number,
            'product_id' => $adjustment->product_id,
            'variant_id' => $adjustment->variant_id,
            'warehouse_id' => $adjustment->warehouse_id,
            'adjustment_date' => $adjustment->adjustment_date->toDateString(),
            'quantity_change' => $adjustment->quantity_change,
            'reason' => $adjustment->reason,
            'notes' => $adjustment->notes,
        ], $data);

        $this->assertDraftPayload($payload);

        $adjustment->update($payload);

        return $adjustment->fresh() ?? $adjustment;
    }

    public function approve(InventoryAdjustment $adjustment, User $user): InventoryAdjustment
    {
        return DB::transaction(function () use ($adjustment, $user) {
            $adjustment = $this->lock($adjustment);

            if (! $adjustment->status->canApprove()) {
                throw new InvalidArgumentException(__('Adjustment cannot be approved in its current status.'));
            }

            $this->assertReadyForApproval($adjustment);

            $adjustment->update([
                'status' => InventoryAdjustmentStatus::Approved,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            return $adjustment->fresh() ?? $adjustment;
        });
    }

    public function post(InventoryAdjustment $adjustment, User $user): InventoryAdjustment
    {
        return DB::transaction(function () use ($adjustment, $user) {
            $adjustment = $this->lock($adjustment);

            if (! $adjustment->status->canPost()) {
                throw new InvalidArgumentException(__('Adjustment must be approved before posting.'));
            }

            $this->assertReadyForPost($adjustment);

            $ledgerEnabled = (bool) config('inventory.ledger_enabled', false);

            if ($ledgerEnabled) {
                $reason = $adjustment->reasonEnum();

                if ($reason === null) {
                    throw new InvalidArgumentException(__('Adjustment reason is invalid for ledger posting.'));
                }

                try {
                    $this->ledger->post(MovementCommand::fromArray([
                        'warehouse_id' => (int) $adjustment->warehouse_id,
                        'product_id' => $adjustment->product_id,
                        'variant_id' => $adjustment->variant_id,
                        'quantity' => $adjustment->quantity_change,
                        'reserved_delta' => 0,
                        'movement_type' => $reason->movementType(),
                        'reason_code' => $reason->value,
                        'idempotency_key' => sprintf('adjustment:%d:post', $adjustment->id),
                        'occurred_at' => $adjustment->adjustment_date->startOfDay(),
                        'reference_type' => InventoryAdjustment::class,
                        'reference_id' => $adjustment->id,
                        'created_by' => $user->id,
                        'notes' => $adjustment->notes ?? $adjustment->reference_number,
                    ]));
                } catch (InsufficientStockException $exception) {
                    throw new InvalidArgumentException(
                        __('Insufficient stock to post this adjustment.'),
                        0,
                        $exception,
                    );
                }
            }

            $adjustment->update([
                'status' => InventoryAdjustmentStatus::Posted,
                'posted_by' => $user->id,
                'posted_at' => now(),
            ]);

            return $adjustment->fresh() ?? $adjustment;
        });
    }

    public function cancel(InventoryAdjustment $adjustment, User $user): InventoryAdjustment
    {
        return DB::transaction(function () use ($adjustment, $user) {
            $adjustment = $this->lock($adjustment);

            if (! $adjustment->status->canCancel()) {
                throw new InvalidArgumentException(__('Adjustment cannot be cancelled in its current status.'));
            }

            $adjustment->update([
                'status' => InventoryAdjustmentStatus::Cancelled,
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
            ]);

            return $adjustment->fresh() ?? $adjustment;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertDraftPayload(array $data): void
    {
        $qty = (int) ($data['quantity_change'] ?? 0);
        if ($qty === 0) {
            throw new InvalidArgumentException(__('Adjustment quantity cannot be zero.'));
        }

        if (empty($data['warehouse_id'])) {
            throw new InvalidArgumentException(__('Warehouse is required for new adjustments.'));
        }

        $reason = InventoryAdjustmentReason::tryFrom((string) ($data['reason'] ?? ''));
        if ($reason === null) {
            throw new InvalidArgumentException(__('Invalid adjustment reason.'));
        }

        $product = Product::query()->whereKey($data['product_id'] ?? null)->first();
        if ($product === null || ! $product->is_active) {
            throw new InvalidArgumentException(__('Product must be active.'));
        }

        $warehouse = Warehouse::query()->whereKey($data['warehouse_id'])->first();
        if ($warehouse === null || ! $warehouse->is_active) {
            throw new InvalidArgumentException(__('Warehouse must be active.'));
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

    protected function assertReadyForApproval(InventoryAdjustment $adjustment): void
    {
        if ($adjustment->warehouse_id === null) {
            throw new InvalidArgumentException(__('Assign a warehouse before approving this adjustment.'));
        }

        if ($adjustment->quantity_change === 0) {
            throw new InvalidArgumentException(__('Adjustment quantity cannot be zero.'));
        }

        if ($adjustment->reasonEnum() === null) {
            throw new InvalidArgumentException(__('Adjustment reason must be a supported code before approval.'));
        }
    }

    protected function assertReadyForPost(InventoryAdjustment $adjustment): void
    {
        $this->assertReadyForApproval($adjustment);

        $warehouse = $adjustment->warehouse ?? Warehouse::query()->find($adjustment->warehouse_id);
        if ($warehouse === null || ! $warehouse->is_active) {
            throw new InvalidArgumentException(__('Warehouse must be active to post.'));
        }

        $product = $adjustment->product ?? Product::query()->find($adjustment->product_id);
        if ($product === null || ! $product->is_active) {
            throw new InvalidArgumentException(__('Product must be active to post.'));
        }

        if ($adjustment->variant_id !== null) {
            $variant = $adjustment->variant ?? Variant::query()->find($adjustment->variant_id);
            if ($variant === null || ! $variant->is_active || (int) $variant->product_id !== (int) $adjustment->product_id) {
                throw new InvalidArgumentException(__('Variant must be active and belong to the product.'));
            }
        }
    }

    protected function lock(InventoryAdjustment $adjustment): InventoryAdjustment
    {
        return InventoryAdjustment::query()->whereKey($adjustment->id)->lockForUpdate()->firstOrFail();
    }
}
