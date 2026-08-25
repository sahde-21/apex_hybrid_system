<?php

namespace App\Console\Commands;

use App\Services\Inventory\AdjustmentTransferPreflightService;
use Illuminate\Console\Command;

class InventoryAdjustmentTransferPreflightCommand extends Command
{
    protected $signature = 'scf:inventory-adjustment-transfer-preflight
                            {--json : Machine-readable output}';

    protected $description = 'Read-only preflight for P0.4 adjustments/transfers (does not enable ledger)';

    public function handle(AdjustmentTransferPreflightService $preflight): int
    {
        $result = $preflight->check();

        if ($this->option('json')) {
            $this->line((string) json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $result->passed ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Adjustment / transfer preflight (read-only)');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Result', $result->passed ? 'PASS' : 'FAIL'],
                ['ledger_enabled', $result->ledgerEnabled ? 'true' : 'false'],
                ['Open adjustments missing warehouse', $result->adjustmentsMissingWarehouse],
                ['Open adjustments invalid reason', $result->adjustmentsInvalidReason],
                ['Transfers same warehouse', $result->transfersSameWarehouse],
                ['In-transit transfers', $result->inTransitCount],
            ],
        );

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
