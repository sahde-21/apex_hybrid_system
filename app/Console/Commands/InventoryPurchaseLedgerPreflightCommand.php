<?php

namespace App\Console\Commands;

use App\Services\Inventory\PurchaseLedgerPreflightService;
use Illuminate\Console\Command;

class InventoryPurchaseLedgerPreflightCommand extends Command
{
    protected $signature = 'scf:inventory-purchase-ledger-preflight
                            {--json : Machine-readable output}';

    protected $description = 'Read-only preflight for P0.5 purchase receipts/returns (does not enable ledger)';

    public function handle(PurchaseLedgerPreflightService $preflight): int
    {
        $result = $preflight->check();

        if ($this->option('json')) {
            $this->line((string) json_encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $result->passed ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Purchase ledger preflight (read-only)');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Result', $result->passed ? 'PASS' : 'FAIL'],
                ['ledger_enabled', $result->ledgerEnabled ? 'true' : 'false'],
                ['Receivable POs missing warehouse', $result->receivableOrdersMissingWarehouse],
                ['Open POs with partial receipts', $result->openOrdersWithPartialReceipts],
                ['Receipt documents', $result->receiptCount],
                ['Return documents', $result->returnCount],
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
