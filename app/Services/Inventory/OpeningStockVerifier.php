<?php

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Support\Inventory\OpeningStockIdentity;
use App\Support\Inventory\OpeningStockVerificationResult;

class OpeningStockVerifier
{
    public function __construct(
        protected OpeningStockPlanner $planner,
    ) {}

    /**
     * @param  array{
     *     warehouse_id?: int|null,
     *     warehouse_code?: string|null,
     *     create_default_warehouse?: bool
     * }  $options
     */
    public function verify(array $options = []): OpeningStockVerificationResult
    {
        $plan = $this->planner->plan([
            ...$options,
            'create_default_warehouse' => false,
            'allow_create_warehouse' => false,
        ]);

        $failures = [];
        $warnings = $plan->warnings;

        if ($plan->blockers !== []) {
            $failures = array_merge($failures, $plan->blockers);
        }

        if ($plan->warehouse === null) {
            $failures[] = __('Warehouse could not be resolved for verification.');

            return new OpeningStockVerificationResult(
                passed: false,
                warehouseId: null,
                warehouseCode: null,
                productsScanned: $plan->productsScanned,
                variantsScanned: $plan->variantsScanned,
                identitiesExpected: 0,
                stockLevelsFound: 0,
                openingMovementsFound: 0,
                quantityMismatches: 0,
                duplicateOpeningKeys: 0,
                negativeSourceQuantities: count($plan->negatives),
                orphanProductQuantities: count($plan->orphans),
                registersWithoutWarehouse: $plan->registersWithoutWarehouse,
                expectedTotalQuantity: 0,
                ledgerTotalQuantity: 0,
                failures: array_values(array_unique($failures)),
                warnings: $warnings,
            );
        }

        $quantityMismatches = 0;
        $levelsFound = 0;
        $movementsFound = 0;
        $ledgerTotal = 0;
        $keys = [];

        foreach ($plan->identities as $identity) {
            $keys[] = $identity->idempotencyKey;

            $level = $this->findLevel($identity);
            $movement = StockMovement::query()
                ->where('idempotency_key', $identity->idempotencyKey)
                ->where('movement_type', StockMovementType::OpeningBalance)
                ->first();

            if ($level === null) {
                $quantityMismatches++;
                $failures[] = __('Missing stock level for :key', ['key' => $identity->idempotencyKey]);

                continue;
            }

            $levelsFound++;
            $ledgerTotal += (int) $level->on_hand;

            if ((int) $level->on_hand !== $identity->quantity) {
                $quantityMismatches++;
                $failures[] = __('Level qty mismatch for :key (expected :expected, found :found)', [
                    'key' => $identity->idempotencyKey,
                    'expected' => $identity->quantity,
                    'found' => $level->on_hand,
                ]);
            }

            if ($movement === null) {
                $quantityMismatches++;
                $failures[] = __('Missing opening movement for :key', ['key' => $identity->idempotencyKey]);

                continue;
            }

            $movementsFound++;

            if ((int) $movement->quantity !== $identity->quantity) {
                $quantityMismatches++;
                $failures[] = __('Movement qty mismatch for :key', ['key' => $identity->idempotencyKey]);
            }
        }

        $duplicateOpeningKeys = 0;
        if ($keys !== []) {
            $duplicateOpeningKeys = StockMovement::query()
                ->select('idempotency_key')
                ->whereIn('idempotency_key', $keys)
                ->groupBy('idempotency_key')
                ->havingRaw('COUNT(*) > 1')
                ->count();
        }

        if ($duplicateOpeningKeys > 0) {
            $failures[] = __('Duplicate opening idempotency keys detected: :count', [
                'count' => $duplicateOpeningKeys,
            ]);
        }

        if ($plan->expectedTotalQuantity !== $ledgerTotal && $plan->identities !== []) {
            // already counted per-identity; keep aggregate note
            $failures[] = __('Expected total quantity :expected does not match ledger total :found.', [
                'expected' => $plan->expectedTotalQuantity,
                'found' => $ledgerTotal,
            ]);
        }

        $passed = $failures === []
            && $plan->negatives === []
            && $plan->orphans === []
            && $quantityMismatches === 0
            && $duplicateOpeningKeys === 0
            && $levelsFound === count($plan->identities)
            && $movementsFound === count($plan->identities);

        return new OpeningStockVerificationResult(
            passed: $passed,
            warehouseId: $plan->warehouse->id,
            warehouseCode: $plan->warehouse->code,
            productsScanned: $plan->productsScanned,
            variantsScanned: $plan->variantsScanned,
            identitiesExpected: count($plan->identities),
            stockLevelsFound: $levelsFound,
            openingMovementsFound: $movementsFound,
            quantityMismatches: $quantityMismatches,
            duplicateOpeningKeys: $duplicateOpeningKeys,
            negativeSourceQuantities: count($plan->negatives),
            orphanProductQuantities: count($plan->orphans),
            registersWithoutWarehouse: $plan->registersWithoutWarehouse,
            expectedTotalQuantity: $plan->expectedTotalQuantity,
            ledgerTotalQuantity: $ledgerTotal,
            failures: array_values(array_unique($failures)),
            warnings: $warnings,
        );
    }

    protected function findLevel(OpeningStockIdentity $identity): ?StockLevel
    {
        return StockLevel::query()
            ->where('warehouse_id', $identity->warehouseId)
            ->where('product_id', $identity->productId)
            ->when(
                $identity->variantId === null,
                fn ($query) => $query->whereNull('variant_id'),
                fn ($query) => $query->where('variant_id', $identity->variantId),
            )
            ->first();
    }
}
