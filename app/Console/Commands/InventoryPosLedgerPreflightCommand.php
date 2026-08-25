<?php

namespace App\Console\Commands;

use App\Services\Inventory\PosLedgerPreflightService;
use Illuminate\Console\Command;

class InventoryPosLedgerPreflightCommand extends Command
{
    protected $signature = 'scf:inventory-pos-ledger-preflight
                            {--warehouse-id= : Opening-stock warehouse id for verification}
                            {--warehouse-code= : Opening-stock warehouse code for verification}
                            {--json : Machine-readable output}';

    protected $description = 'Read-only preflight before enabling inventory.ledger_enabled for POS (does not enable the flag)';

    public function handle(PosLedgerPreflightService $preflight): int
    {
        $result = $preflight->check([
            'warehouse_id' => $this->option('warehouse-id') !== null && $this->option('warehouse-id') !== ''
                ? (int) $this->option('warehouse-id')
                : null,
            'warehouse_code' => $this->option('warehouse-code') !== null && $this->option('warehouse-code') !== ''
                ? (string) $this->option('warehouse-code')
                : null,
        ]);

        if ($this->option('json')) {
            $this->line((string) json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $result->passed ? self::SUCCESS : self::FAILURE;
        }

        $this->info('POS inventory ledger preflight (read-only)');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Result', $result->passed ? 'PASS' : 'FAIL'],
                ['ledger_enabled', $result->ledgerEnabled ? 'true' : 'false'],
                ['Active registers', $result->activeRegisterCount],
                ['Registers without warehouse', count($result->registersWithoutWarehouse)],
                ['Inactive warehouse links', count($result->inactiveWarehouseRegisterCodes)],
                ['Opening stock passed', $result->openingStockPassed ? 'yes' : 'no'],
                ['Mirror mismatches', $result->mirrorMismatchCount],
                ['Products checked', $result->productsChecked],
                ['Variants checked', $result->variantsChecked],
            ],
        );

        if ($result->registersWithoutWarehouse !== []) {
            $this->warn('Registers missing warehouse_id: '.implode(', ', $result->registersWithoutWarehouse));
        }

        foreach ($result->warnings as $warning) {
            $this->warn($warning);
        }

        foreach ($result->failures as $failure) {
            $this->error($failure);
        }

        $this->newLine();
        $this->line('This command never enables inventory.ledger_enabled.');

        return $result->passed ? self::SUCCESS : self::FAILURE;
    }
}
