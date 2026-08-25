<?php

namespace App\Services\Inventory;

use App\Models\PosRegister;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Variant;
use App\Models\Warehouse;
use App\Support\Inventory\PosLedgerPreflightResult;

class PosLedgerPreflightService
{
    public function __construct(
        protected OpeningStockVerifier $openingStockVerifier,
    ) {}

    /**
     * Read-only readiness check before enabling inventory.ledger_enabled for POS.
     * Never mutates data or enables the feature flag.
     *
     * @param  array{warehouse_id?: int|null, warehouse_code?: string|null}  $options
     */
    public function check(array $options = []): PosLedgerPreflightResult
    {
        $failures = [];
        $warnings = [];

        $activeRegisters = PosRegister::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'warehouse_id', 'is_active']);

        $registersWithoutWarehouse = $activeRegisters
            ->filter(fn (PosRegister $register) => $register->warehouse_id === null)
            ->values();

        foreach ($registersWithoutWarehouse as $register) {
            $failures[] = __('Active POS register :code has no warehouse_id assigned.', [
                'code' => $register->code,
            ]);
        }

        $inactiveWarehouseLinks = [];
        $registerWarehouseIds = $activeRegisters
            ->pluck('warehouse_id')
            ->filter()
            ->unique()
            ->values();

        if ($registerWarehouseIds->isNotEmpty()) {
            $warehouses = Warehouse::query()
                ->whereIn('id', $registerWarehouseIds->all())
                ->get()
                ->keyBy('id');

            foreach ($activeRegisters as $register) {
                if ($register->warehouse_id === null) {
                    continue;
                }

                $warehouse = $warehouses->get($register->warehouse_id);

                if ($warehouse === null) {
                    $failures[] = __('Active POS register :code references missing warehouse #:id.', [
                        'code' => $register->code,
                        'id' => $register->warehouse_id,
                    ]);

                    continue;
                }

                if (! $warehouse->is_active) {
                    $inactiveWarehouseLinks[] = $register->code;
                    $failures[] = __('Active POS register :code is linked to inactive warehouse :warehouse.', [
                        'code' => $register->code,
                        'warehouse' => $warehouse->code,
                    ]);
                }
            }
        }

        $opening = $this->openingStockVerifier->verify([
            'warehouse_id' => $options['warehouse_id'] ?? null,
            'warehouse_code' => $options['warehouse_code'] ?? null,
            'create_default_warehouse' => false,
        ]);

        if (! $opening->passed) {
            foreach ($opening->failures as $failure) {
                $failures[] = __('Opening stock: :message', ['message' => $failure]);
            }
        }

        foreach ($opening->warnings as $warning) {
            $warnings[] = __('Opening stock: :message', ['message' => $warning]);
        }

        $mirror = $this->reconcileLegacyMirrors();

        $failures = array_merge($failures, $mirror['failures']);
        $warnings = array_merge($warnings, $mirror['warnings']);

        if ((bool) config('inventory.ledger_enabled', false)) {
            $warnings[] = __('inventory.ledger_enabled is already true. Preflight is informational only and does not change the flag.');
        } else {
            $warnings[] = __('inventory.ledger_enabled remains false. Enable it manually only after preflight passes.');
        }

        $passed = $failures === []
            && $registersWithoutWarehouse->isEmpty()
            && $inactiveWarehouseLinks === []
            && $opening->passed
            && $mirror['mismatch_count'] === 0;

        return new PosLedgerPreflightResult(
            passed: $passed,
            ledgerEnabled: (bool) config('inventory.ledger_enabled', false),
            activeRegisterCount: $activeRegisters->count(),
            registersWithoutWarehouse: array_values($registersWithoutWarehouse->pluck('code')->all()),
            inactiveWarehouseRegisterCodes: $inactiveWarehouseLinks,
            openingStockPassed: $opening->passed,
            mirrorMismatchCount: $mirror['mismatch_count'],
            productsChecked: $mirror['products_checked'],
            variantsChecked: $mirror['variants_checked'],
            failures: array_values(array_unique($failures)),
            warnings: array_values(array_unique($warnings)),
        );
    }

    /**
     * @return array{mismatch_count: int, products_checked: int, variants_checked: int, failures: list<string>, warnings: list<string>}
     */
    protected function reconcileLegacyMirrors(): array
    {
        $failures = [];
        $warnings = [];
        $mismatchCount = 0;

        $productIds = StockLevel::query()
            ->whereNull('variant_id')
            ->distinct()
            ->pluck('product_id');

        $productsChecked = 0;

        foreach ($productIds as $productId) {
            $productsChecked++;
            $sum = (int) StockLevel::query()
                ->where('product_id', $productId)
                ->whereNull('variant_id')
                ->sum('on_hand');

            $legacy = (int) Product::query()->whereKey($productId)->value('stock_quantity');

            if ($legacy !== $sum) {
                $mismatchCount++;
                $failures[] = __('Product #:id stock_quantity (:legacy) != SUM(stock_levels.on_hand) (:sum).', [
                    'id' => $productId,
                    'legacy' => $legacy,
                    'sum' => $sum,
                ]);
            }
        }

        $variantIds = StockLevel::query()
            ->whereNotNull('variant_id')
            ->distinct()
            ->pluck('variant_id');

        $variantsChecked = 0;

        foreach ($variantIds as $variantId) {
            $variantsChecked++;
            $level = StockLevel::query()->where('variant_id', $variantId)->first();
            $sum = (int) StockLevel::query()
                ->where('variant_id', $variantId)
                ->when($level?->product_id, fn ($q) => $q->where('product_id', $level->product_id))
                ->sum('on_hand');

            $legacy = (int) Variant::query()->whereKey($variantId)->value('stock_quantity');

            if ($legacy !== $sum) {
                $mismatchCount++;
                $failures[] = __('Variant #:id stock_quantity (:legacy) != SUM(stock_levels.on_hand) (:sum).', [
                    'id' => $variantId,
                    'legacy' => $legacy,
                    'sum' => $sum,
                ]);
            }
        }

        if ($productsChecked === 0 && $variantsChecked === 0) {
            $warnings[] = __('No stock_levels rows found for mirror reconciliation. Run opening stock import first.');
        }

        return [
            'mismatch_count' => $mismatchCount,
            'products_checked' => $productsChecked,
            'variants_checked' => $variantsChecked,
            'failures' => $failures,
            'warnings' => $warnings,
        ];
    }
}
