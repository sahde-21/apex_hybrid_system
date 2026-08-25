<?php

namespace App\Console\Commands;

use App\Services\Inventory\OpeningStockVerifier;
use Illuminate\Console\Command;

class InventoryOpeningStockVerifyCommand extends Command
{
    protected $signature = 'scf:inventory-opening-stock-verify
                            {--warehouse-id= : Explicit warehouse id}
                            {--warehouse-code= : Explicit warehouse code}
                            {--json : Machine-readable output}';

    protected $description = 'Verify opening stock ledger balances against product/variant quantities (read-only)';

    public function handle(OpeningStockVerifier $verifier): int
    {
        $result = $verifier->verify([
            'warehouse_id' => $this->option('warehouse-id') !== null && $this->option('warehouse-id') !== ''
                ? (int) $this->option('warehouse-id')
                : null,
            'warehouse_code' => $this->option('warehouse-code') !== null && $this->option('warehouse-code') !== ''
                ? (string) $this->option('warehouse-code')
                : null,
            'create_default_warehouse' => false,
        ]);

        if ($this->option('json')) {
            $this->line((string) json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $result->passed ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Opening stock verification');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Result', $result->passed ? 'PASS' : 'FAIL'],
                ['Warehouse', ($result->warehouseCode ?? '—').' / '.($result->warehouseId ?? '—')],
                ['Products scanned', $result->productsScanned],
                ['Variants scanned', $result->variantsScanned],
                ['Identities expected', $result->identitiesExpected],
                ['Stock levels found', $result->stockLevelsFound],
                ['Opening movements found', $result->openingMovementsFound],
                ['Quantity mismatches', $result->quantityMismatches],
                ['Duplicate opening keys', $result->duplicateOpeningKeys],
                ['Negative source quantities', $result->negativeSourceQuantities],
                ['Orphan product quantities', $result->orphanProductQuantities],
                ['Registers without warehouse', $result->registersWithoutWarehouse],
                ['Expected total qty', $result->expectedTotalQuantity],
                ['Ledger total qty', $result->ledgerTotalQuantity],
            ],
        );

        foreach ($result->warnings as $warning) {
            $this->warn($warning);
        }

        foreach ($result->failures as $failure) {
            $this->error($failure);
        }

        return $result->passed ? self::SUCCESS : self::FAILURE;
    }
}
