<?php

namespace App\Support\Inventory;

final class OpeningStockVerificationResult
{
    /**
     * @param  list<string>  $failures
     * @param  list<string>  $warnings
     */
    public function __construct(
        public bool $passed,
        public ?int $warehouseId,
        public ?string $warehouseCode,
        public int $productsScanned,
        public int $variantsScanned,
        public int $identitiesExpected,
        public int $stockLevelsFound,
        public int $openingMovementsFound,
        public int $quantityMismatches,
        public int $duplicateOpeningKeys,
        public int $negativeSourceQuantities,
        public int $orphanProductQuantities,
        public int $registersWithoutWarehouse,
        public int $expectedTotalQuantity,
        public int $ledgerTotalQuantity,
        public array $failures = [],
        public array $warnings = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'passed' => $this->passed,
            'warehouse_id' => $this->warehouseId,
            'warehouse_code' => $this->warehouseCode,
            'products_scanned' => $this->productsScanned,
            'variants_scanned' => $this->variantsScanned,
            'identities_expected' => $this->identitiesExpected,
            'stock_levels_found' => $this->stockLevelsFound,
            'opening_movements_found' => $this->openingMovementsFound,
            'quantity_mismatches' => $this->quantityMismatches,
            'duplicate_opening_keys' => $this->duplicateOpeningKeys,
            'negative_source_quantities' => $this->negativeSourceQuantities,
            'orphan_product_quantities' => $this->orphanProductQuantities,
            'registers_without_warehouse' => $this->registersWithoutWarehouse,
            'expected_total_quantity' => $this->expectedTotalQuantity,
            'ledger_total_quantity' => $this->ledgerTotalQuantity,
            'failures' => $this->failures,
            'warnings' => $this->warnings,
            'result' => $this->passed ? 'PASS' : 'FAIL',
        ];
    }
}
