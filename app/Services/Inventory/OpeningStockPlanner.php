<?php

namespace App\Services\Inventory;

use App\Models\PosRegister;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Variant;
use App\Models\Warehouse;
use App\Support\Inventory\OpeningStockIdentity;
use App\Support\Inventory\OpeningStockPlan;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use InvalidArgumentException;

class OpeningStockPlanner
{
    public function idempotencyKey(int $warehouseId, int $productId, ?int $variantId): string
    {
        $version = (string) config('inventory.opening_stock.import_version', 'v1');

        return sprintf(
            'opening:%s:%d:p%d:v%d',
            $version,
            $warehouseId,
            $productId,
            $variantId ?? 0,
        );
    }

    /**
     * @param  array{
     *     warehouse_id?: int|null,
     *     warehouse_code?: string|null,
     *     create_default_warehouse?: bool,
     *     allow_create_warehouse?: bool
     * }  $options
     */
    public function plan(array $options = []): OpeningStockPlan
    {
        $createDefaultRequested = (bool) ($options['create_default_warehouse'] ?? false);
        $allowCreateWarehouse = (bool) ($options['allow_create_warehouse'] ?? false);

        $blockers = [];
        $warnings = [];
        $wouldCreateWarehouse = false;
        $warehouse = null;

        try {
            $warehouse = $this->resolveWarehouse(
                warehouseId: isset($options['warehouse_id']) ? (int) $options['warehouse_id'] : null,
                warehouseCode: isset($options['warehouse_code']) && $options['warehouse_code'] !== ''
                    ? (string) $options['warehouse_code']
                    : null,
                createDefaultRequested: $createDefaultRequested,
                allowCreate: $allowCreateWarehouse,
                wouldCreate: $wouldCreateWarehouse,
            );
        } catch (InvalidArgumentException $exception) {
            if ($wouldCreateWarehouse) {
                $warnings[] = $exception->getMessage();
                // Provisional identity for dry-run totals; keys finalized after MAIN is created.
                $warehouse = new Warehouse([
                    'id' => 0,
                    'code' => (string) config('inventory.opening_stock.default_warehouse_code', 'MAIN'),
                    'name' => (string) config('inventory.opening_stock.default_warehouse_name', 'Main Warehouse'),
                    'is_active' => true,
                ]);
            } else {
                $blockers[] = $exception->getMessage();
            }
        }

        /** @var EloquentCollection<int, Product> $products */
        $products = Product::query()
            ->with(['variants'])
            ->orderBy('id')
            ->get();

        $negatives = [];
        $orphans = [];
        $identities = [];
        $zeroSkipped = 0;
        $variantsScanned = 0;

        $warehouseIdForKeys = $warehouse !== null ? (int) $warehouse->id : null;
        $provisionalWarehouse = $wouldCreateWarehouse && $warehouse !== null && (int) $warehouse->id === 0;

        foreach ($products as $product) {
            /** @var EloquentCollection<int, Variant> $variants */
            $variants = $product->variants;
            $variantsScanned += $variants->count();

            if ($variants->isNotEmpty()) {
                if ((int) $product->stock_quantity !== 0) {
                    $orphans[] = [
                        'product_id' => (int) $product->id,
                        'sku' => $product->sku,
                        'stock_quantity' => (int) $product->stock_quantity,
                        'variant_count' => $variants->count(),
                    ];
                }

                foreach ($variants as $variant) {
                    $qty = (int) $variant->stock_quantity;

                    if ($qty < 0) {
                        $negatives[] = [
                            'type' => 'variant',
                            'id' => (int) $variant->id,
                            'sku' => $variant->sku,
                            'stock_quantity' => $qty,
                        ];

                        continue;
                    }

                    if ($qty === 0) {
                        $zeroSkipped++;

                        continue;
                    }

                    if ($warehouseIdForKeys === null) {
                        continue;
                    }

                    $identities[] = new OpeningStockIdentity(
                        warehouseId: (int) $warehouseIdForKeys,
                        productId: (int) $product->id,
                        variantId: (int) $variant->id,
                        quantity: $qty,
                        idempotencyKey: $this->idempotencyKey((int) $warehouseIdForKeys, (int) $product->id, (int) $variant->id),
                        sku: $variant->sku,
                        productActive: (bool) $product->is_active,
                        variantActive: (bool) $variant->is_active,
                    );
                }

                continue;
            }

            $qty = (int) $product->stock_quantity;

            if ($qty < 0) {
                $negatives[] = [
                    'type' => 'product',
                    'id' => (int) $product->id,
                    'sku' => $product->sku,
                    'stock_quantity' => $qty,
                ];

                continue;
            }

            if ($qty === 0) {
                $zeroSkipped++;

                continue;
            }

            if ($warehouseIdForKeys === null) {
                continue;
            }

            $identities[] = new OpeningStockIdentity(
                warehouseId: (int) $warehouseIdForKeys,
                productId: (int) $product->id,
                variantId: null,
                quantity: $qty,
                idempotencyKey: $this->idempotencyKey((int) $warehouseIdForKeys, (int) $product->id, null),
                sku: $product->sku,
                productActive: (bool) $product->is_active,
                variantActive: true,
            );
        }

        if ($negatives !== []) {
            $blockers[] = __('Negative source stock detected (:count). Import aborted.', [
                'count' => count($negatives),
            ]);
        }

        if ($orphans !== []) {
            $blockers[] = __('Orphan product-level stock on variant products (:count). Import aborted.', [
                'count' => count($orphans),
            ]);
        }

        if ($warehouse === null && ! $wouldCreateWarehouse) {
            // already blocked via resolve exception
        } elseif ($provisionalWarehouse) {
            $warnings[] = __('Dry-run: default warehouse MAIN would be created on --execute; identity keys use provisional warehouse id 0.');
        }

        $existingOpeningMovements = 0;
        $expectedNewMovements = count($identities);

        if ($warehouse !== null && ! $provisionalWarehouse && $identities !== []) {
            $keys = array_map(fn (OpeningStockIdentity $identity) => $identity->idempotencyKey, $identities);
            $existingKeys = StockMovement::query()
                ->whereIn('idempotency_key', $keys)
                ->pluck('idempotency_key')
                ->all();
            $existingOpeningMovements = count($existingKeys);
            $expectedNewMovements = count($identities) - $existingOpeningMovements;

            if ($existingOpeningMovements > 0) {
                $warnings[] = __('Existing opening movements will be replayed idempotently (:count).', [
                    'count' => $existingOpeningMovements,
                ]);
            }
        }

        $registersWithoutWarehouse = PosRegister::query()->whereNull('warehouse_id')->count();

        if ($registersWithoutWarehouse > 0) {
            $warnings[] = __('POS registers without warehouse_id: :count (P0.3 concern; not modified).', [
                'count' => $registersWithoutWarehouse,
            ]);
        }

        return new OpeningStockPlan(
            warehouse: $provisionalWarehouse ? null : $warehouse,
            warehouseResolved: $warehouse !== null && ! $provisionalWarehouse,
            wouldCreateWarehouse: $wouldCreateWarehouse,
            identities: $identities,
            blockers: array_values(array_unique($blockers)),
            warnings: $warnings,
            orphans: $orphans,
            negatives: $negatives,
            productsScanned: $products->count(),
            variantsScanned: $variantsScanned,
            zeroSkipped: $zeroSkipped,
            registersWithoutWarehouse: $registersWithoutWarehouse,
            existingOpeningMovements: $existingOpeningMovements,
            expectedNewMovements: max(0, $expectedNewMovements),
            expectedTotalQuantity: array_sum(array_map(fn (OpeningStockIdentity $i) => $i->quantity, $identities)),
        );
    }

    /**
     * Resolution order: --warehouse-id, --warehouse-code, config id/code, single active warehouse,
     * optional explicit MAIN creation.
     *
     * @param-out bool $wouldCreate
     */
    public function resolveWarehouse(
        ?int $warehouseId,
        ?string $warehouseCode,
        bool $createDefaultRequested,
        bool $allowCreate,
        bool &$wouldCreate = false,
    ): Warehouse {
        $wouldCreate = false;

        if ($warehouseId !== null && $warehouseId > 0) {
            return $this->requireActiveWarehouse(
                Warehouse::query()->find($warehouseId),
                __('Warehouse id :id was not found.', ['id' => $warehouseId]),
                __('Warehouse id :id is inactive.', ['id' => $warehouseId]),
            );
        }

        if ($warehouseCode !== null && $warehouseCode !== '') {
            return $this->requireActiveWarehouse(
                Warehouse::query()->where('code', $warehouseCode)->first(),
                __('Warehouse code :code was not found.', ['code' => $warehouseCode]),
                __('Warehouse code :code is inactive.', ['code' => $warehouseCode]),
            );
        }

        $configuredId = config('inventory.opening_stock.warehouse_id');
        if ($configuredId !== null && $configuredId !== '') {
            return $this->requireActiveWarehouse(
                Warehouse::query()->find((int) $configuredId),
                __('Configured opening stock warehouse_id was not found.'),
                __('Configured opening stock warehouse is inactive.'),
            );
        }

        $configuredCode = (string) config('inventory.opening_stock.warehouse_code', '');
        if ($configuredCode !== '') {
            return $this->requireActiveWarehouse(
                Warehouse::query()->where('code', $configuredCode)->first(),
                __('Configured opening stock warehouse_code was not found.'),
                __('Configured opening stock warehouse is inactive.'),
            );
        }

        $active = Warehouse::query()->where('is_active', true)->orderBy('id')->get();

        if ($active->count() === 1) {
            return $active->first();
        }

        if ($active->count() > 1) {
            throw new InvalidArgumentException(
                __('Multiple active warehouses exist. Pass --warehouse-id or --warehouse-code explicitly.')
            );
        }

        $defaultCode = (string) config('inventory.opening_stock.default_warehouse_code', 'MAIN');
        $existingDefault = Warehouse::query()->where('code', $defaultCode)->first();

        if ($existingDefault !== null) {
            return $this->requireActiveWarehouse(
                $existingDefault,
                __('Default warehouse code :code was not found.', ['code' => $defaultCode]),
                __('Default warehouse code :code exists but is inactive.', ['code' => $defaultCode]),
            );
        }

        if (! $createDefaultRequested) {
            throw new InvalidArgumentException(
                __('No warehouse resolved. Pass --warehouse-id/--warehouse-code or --create-default-warehouse.')
            );
        }

        if (! $allowCreate) {
            $wouldCreate = true;

            throw new InvalidArgumentException(
                __('No warehouse exists. Pass --execute --create-default-warehouse to create MAIN.')
            );
        }

        return Warehouse::query()->create([
            'code' => $defaultCode,
            'name' => (string) config('inventory.opening_stock.default_warehouse_name', 'Main Warehouse'),
            'is_active' => true,
        ]);
    }

    protected function requireActiveWarehouse(?Warehouse $warehouse, string $missingMessage, string $inactiveMessage): Warehouse
    {
        if ($warehouse === null) {
            throw new InvalidArgumentException($missingMessage);
        }

        if (! $warehouse->is_active) {
            throw new InvalidArgumentException($inactiveMessage);
        }

        return $warehouse;
    }
}
