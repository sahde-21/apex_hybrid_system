<?php

namespace App\Console\Commands;

use App\Services\Inventory\OpeningStockImportService;
use Illuminate\Console\Command;
use Throwable;

class InventoryOpeningStockCommand extends Command
{
    protected $signature = 'scf:inventory-opening-stock
                            {--dry-run : Plan and validate without writing}
                            {--execute : Perform the opening stock import}
                            {--warehouse-id= : Explicit warehouse id}
                            {--warehouse-code= : Explicit warehouse code}
                            {--create-default-warehouse : Create MAIN warehouse when none exist (execute only)}
                            {--json : Machine-readable output}';

    protected $description = 'Import opening stock into the inventory ledger from product/variant quantities';

    public function handle(OpeningStockImportService $import): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $execute = (bool) $this->option('execute');

        if ($dryRun === $execute) {
            $this->error('Specify exactly one of --dry-run or --execute.');

            return self::FAILURE;
        }

        $options = [
            'warehouse_id' => $this->option('warehouse-id') !== null && $this->option('warehouse-id') !== ''
                ? (int) $this->option('warehouse-id')
                : null,
            'warehouse_code' => $this->option('warehouse-code') !== null && $this->option('warehouse-code') !== ''
                ? (string) $this->option('warehouse-code')
                : null,
            'create_default_warehouse' => (bool) $this->option('create-default-warehouse'),
        ];

        if ($dryRun) {
            $plan = $import->dryRun($options);

            if ($this->option('json')) {
                $this->line((string) json_encode($plan->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $this->renderPlan($plan->toArray(), dryRun: true);
            }

            return $plan->isClean() ? self::SUCCESS : self::FAILURE;
        }

        try {
            $result = $import->execute($options);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $payload = [
            'plan' => $result['plan']->toArray(),
            'verification' => $result['verification']->toArray(),
            'posted' => $result['posted'],
            'replayed' => $result['replayed'],
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->info('Opening stock import completed.');
            $this->renderPlan($payload['plan'], dryRun: false);
            $this->line('Posted: '.$payload['posted'].' · Replayed: '.$payload['replayed']);
            $this->line('Verification: '.$payload['verification']['result']);
        }

        return $result['verification']->passed ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  array<string, mixed>  $plan
     */
    protected function renderPlan(array $plan, bool $dryRun): void
    {
        $this->info($dryRun ? 'Opening stock dry-run' : 'Opening stock plan');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Warehouse', ($plan['warehouse_code'] ?? '—').' / '.($plan['warehouse_id'] ?? '—')],
                ['Warehouse resolved', $plan['warehouse_resolved'] ? 'yes' : 'no'],
                ['Would create MAIN', ! empty($plan['would_create_warehouse']) ? 'yes' : 'no'],
                ['Products scanned', $plan['products_scanned']],
                ['Variants scanned', $plan['variants_scanned']],
                ['Identities (non-zero)', $plan['identities']],
                ['Expected new movements', $plan['expected_new_movements']],
                ['Expected total qty', $plan['expected_total_quantity']],
                ['Zero skipped', $plan['zero_skipped']],
                ['Existing openings', $plan['existing_opening_movements']],
                ['Registers without warehouse', $plan['registers_without_warehouse']],
                ['Negatives', count($plan['negatives'])],
                ['Orphans', count($plan['orphans'])],
                ['Clean', ! empty($plan['clean']) ? 'yes' : 'no'],
            ],
        );

        foreach ($plan['warnings'] as $warning) {
            $this->warn((string) $warning);
        }

        foreach ($plan['blockers'] as $blocker) {
            $this->error((string) $blocker);
        }
    }
}
