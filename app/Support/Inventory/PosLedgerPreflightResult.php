<?php

namespace App\Support\Inventory;

readonly class PosLedgerPreflightResult
{
    /**
     * @param  list<string>  $registersWithoutWarehouse
     * @param  list<string>  $inactiveWarehouseRegisterCodes
     * @param  list<string>  $failures
     * @param  list<string>  $warnings
     */
    public function __construct(
        public bool $passed,
        public bool $ledgerEnabled,
        public int $activeRegisterCount,
        public array $registersWithoutWarehouse,
        public array $inactiveWarehouseRegisterCodes,
        public bool $openingStockPassed,
        public int $mirrorMismatchCount,
        public int $productsChecked,
        public int $variantsChecked,
        public array $failures,
        public array $warnings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'passed' => $this->passed,
            'ledger_enabled' => $this->ledgerEnabled,
            'active_register_count' => $this->activeRegisterCount,
            'registers_without_warehouse' => $this->registersWithoutWarehouse,
            'inactive_warehouse_register_codes' => $this->inactiveWarehouseRegisterCodes,
            'opening_stock_passed' => $this->openingStockPassed,
            'mirror_mismatch_count' => $this->mirrorMismatchCount,
            'products_checked' => $this->productsChecked,
            'variants_checked' => $this->variantsChecked,
            'failures' => $this->failures,
            'warnings' => $this->warnings,
        ];
    }
}
