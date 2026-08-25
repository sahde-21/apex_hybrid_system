<?php

namespace App\Services\Inventory;

use App\Exceptions\Inventory\InactiveProductException;
use App\Exceptions\Inventory\InactiveVariantException;
use App\Exceptions\Inventory\InactiveWarehouseException;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Exceptions\Inventory\InvalidReservationException;
use App\Exceptions\Inventory\InvalidStockIdentityException;
use App\Exceptions\Inventory\InvalidStockQuantityException;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Variant;
use App\Models\Warehouse;
use App\Support\Inventory\MovementCommand;
use App\Support\Inventory\MovementResult;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Throwable;

class StockLedgerService
{
    /**
     * Post an immutable stock movement and update the locked stock level.
     *
     * Compatibility mirrors (legacy stock_quantity columns) are updated only when
     * inventory.ledger_enabled is true — see syncCompatibilityMirrors().
     */
    public function post(MovementCommand $command): MovementResult
    {
        $existing = StockMovement::query()
            ->where('idempotency_key', $command->idempotencyKey)
            ->first();

        if ($existing !== null) {
            return $this->replayedResult($existing);
        }

        try {
            return DB::transaction(function () use ($command) {
                $existingInside = StockMovement::query()
                    ->where('idempotency_key', $command->idempotencyKey)
                    ->lockForUpdate()
                    ->first();

                if ($existingInside !== null) {
                    return $this->replayedResult($existingInside);
                }

                $this->assertCommandShape($command);

                $warehouse = $this->resolveWarehouse($command);
                $product = $this->resolveProduct($command);
                $variant = $this->resolveVariant($command, $product);

                $level = $this->lockOrCreateLevel(
                    $warehouse->id,
                    $product->id,
                    $variant?->id,
                );

                $quantityBefore = (int) $level->on_hand;
                $reservedBefore = (int) $level->reserved;

                $quantityAfter = $quantityBefore + $command->quantity;
                $reservedAfter = $reservedBefore + $command->reservedDelta;

                $this->assertPostingPolicy(
                    $command,
                    $quantityBefore,
                    $reservedBefore,
                    $quantityAfter,
                    $reservedAfter,
                );

                $movement = StockMovement::query()->create([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'quantity' => $command->quantity,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantityAfter,
                    'reserved_delta' => $command->reservedDelta,
                    'movement_type' => $command->movementType,
                    'reason_code' => $command->reasonCode,
                    'occurred_at' => CarbonImmutable::parse($command->occurredAt),
                    'reference_type' => $command->referenceType,
                    'reference_id' => $command->referenceId,
                    'reference_line_id' => $command->referenceLineId,
                    'idempotency_key' => $command->idempotencyKey,
                    'unit_cost' => $command->unitCost,
                    'notes' => $command->notes,
                    'created_by' => $command->createdBy,
                ]);

                $level->forceFill([
                    'on_hand' => $quantityAfter,
                    'reserved' => $reservedAfter,
                    'version' => (int) $level->version + 1,
                ])->save();

                $this->syncCompatibilityMirrors($level);

                return new MovementResult(
                    movement: $movement->fresh() ?? $movement,
                    level: $level->fresh() ?? $level,
                    replayed: false,
                );
            });
        } catch (UniqueConstraintViolationException $exception) {
            return $this->resolveIdempotentReplay($command, $exception);
        } catch (QueryException $exception) {
            if ($this->isUniqueViolation($exception)) {
                return $this->resolveIdempotentReplay($command, $exception);
            }

            throw $exception;
        }
    }

    /**
     * When inventory.ledger_enabled is true, keep legacy product/variant stock_quantity
     * as SUM(stock_levels.on_hand) across all warehouses for the same identity.
     *
     * When the flag is false (opening stock / infrastructure posts), mirrors are left alone.
     */
    protected function syncCompatibilityMirrors(StockLevel $level): void
    {
        if (! (bool) config('inventory.ledger_enabled', false)) {
            return;
        }

        if ($level->variant_id !== null) {
            $sum = (int) StockLevel::query()
                ->where('product_id', $level->product_id)
                ->where('variant_id', $level->variant_id)
                ->sum('on_hand');

            Variant::query()->whereKey($level->variant_id)->update([
                'stock_quantity' => $sum,
            ]);

            return;
        }

        $sum = (int) StockLevel::query()
            ->where('product_id', $level->product_id)
            ->whereNull('variant_id')
            ->sum('on_hand');

        Product::query()->whereKey($level->product_id)->update([
            'stock_quantity' => $sum,
        ]);
    }

    protected function replayedResult(StockMovement $movement): MovementResult
    {
        $level = StockLevel::query()
            ->where('warehouse_id', $movement->warehouse_id)
            ->where('product_id', $movement->product_id)
            ->when(
                $movement->variant_id === null,
                fn ($query) => $query->whereNull('variant_id'),
                fn ($query) => $query->where('variant_id', $movement->variant_id),
            )
            ->firstOrFail();

        return new MovementResult(
            movement: $movement,
            level: $level,
            replayed: true,
        );
    }

    protected function resolveIdempotentReplay(MovementCommand $command, Throwable $previous): MovementResult
    {
        $existing = StockMovement::query()
            ->where('idempotency_key', $command->idempotencyKey)
            ->first();

        if ($existing === null) {
            throw $previous;
        }

        return $this->replayedResult($existing);
    }

    protected function isUniqueViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = $exception->errorInfo[1] ?? null;
        $message = strtolower($exception->getMessage());

        return $sqlState === '23505'
            || $driverCode === 19
            || str_contains($message, 'unique')
            || str_contains($message, 'duplicate');
    }

    protected function assertCommandShape(MovementCommand $command): void
    {
        if ($command->idempotencyKey === '') {
            throw new InvalidStockQuantityException(__('Idempotency key is required.'));
        }

        if ($command->quantity === 0 && $command->reservedDelta === 0) {
            throw new InvalidStockQuantityException(__('Movement quantity and reserved delta cannot both be zero.'));
        }
    }

    protected function resolveWarehouse(MovementCommand $command): Warehouse
    {
        $warehouse = Warehouse::query()->find($command->warehouseId);

        if ($warehouse === null) {
            throw new InvalidStockIdentityException(__('Warehouse not found.'));
        }

        if (! $command->allowInactive && ! $warehouse->is_active) {
            throw new InactiveWarehouseException;
        }

        return $warehouse;
    }

    protected function resolveProduct(MovementCommand $command): Product
    {
        $product = Product::query()->find($command->productId);

        if ($product === null) {
            throw new InvalidStockIdentityException(__('Product not found.'));
        }

        if (! $command->allowInactive && ! $product->is_active) {
            throw new InactiveProductException;
        }

        return $product;
    }

    protected function resolveVariant(MovementCommand $command, Product $product): ?Variant
    {
        if ($command->variantId === null) {
            return null;
        }

        $variant = Variant::query()->find($command->variantId);

        if ($variant === null) {
            throw new InvalidStockIdentityException(__('Variant not found.'));
        }

        if ((int) $variant->product_id !== (int) $product->id) {
            throw new InvalidStockIdentityException(__('Variant does not belong to the given product.'));
        }

        if (! $command->allowInactive && ! $variant->is_active) {
            throw new InactiveVariantException;
        }

        return $variant;
    }

    protected function lockOrCreateLevel(int $warehouseId, int $productId, ?int $variantId): StockLevel
    {
        $query = StockLevel::query()
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->when(
                $variantId === null,
                fn ($builder) => $builder->whereNull('variant_id'),
                fn ($builder) => $builder->where('variant_id', $variantId),
            );

        $level = (clone $query)->lockForUpdate()->first();

        if ($level !== null) {
            return $level;
        }

        try {
            StockLevel::query()->create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'on_hand' => 0,
                'reserved' => 0,
                'version' => 0,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Concurrent create — fall through to lock.
        } catch (QueryException $exception) {
            if (! $this->isUniqueViolation($exception)) {
                throw $exception;
            }
        }

        $level = $query->lockForUpdate()->first();

        if ($level === null) {
            throw new InvalidStockIdentityException(__('Unable to acquire stock level lock.'));
        }

        return $level;
    }

    protected function assertPostingPolicy(
        MovementCommand $command,
        int $quantityBefore,
        int $reservedBefore,
        int $quantityAfter,
        int $reservedAfter,
    ): void {
        if ($reservedAfter < 0) {
            throw new InvalidReservationException(__('Reserved quantity cannot be negative.'));
        }

        $allowNegative = (bool) config('inventory.allow_negative_stock', false);

        $availableBefore = $quantityBefore - $reservedBefore;
        $availableAfter = $quantityAfter - $reservedAfter;

        if ($command->reservedDelta > 0 && $command->reservedDelta > $availableBefore) {
            throw new InvalidReservationException(__('Cannot reserve more than available stock.'));
        }

        if (! $allowNegative && $availableAfter < 0) {
            $requested = abs(min(0, $command->quantity)) + max(0, $command->reservedDelta);

            throw new InsufficientStockException(
                message: __('Insufficient stock available.'),
                available: max(0, $availableBefore),
                requested: $requested > 0 ? $requested : abs($command->quantity),
            );
        }

        if (! $allowNegative && $quantityAfter < $reservedAfter) {
            throw new InsufficientStockException(
                message: __('On-hand quantity cannot be less than reserved quantity.'),
                available: max(0, $availableBefore),
                requested: abs($command->quantity),
            );
        }
    }
}
