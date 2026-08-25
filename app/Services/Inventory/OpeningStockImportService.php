<?php

namespace App\Services\Inventory;

use App\Enums\StockMovementType;
use App\Support\Inventory\MovementCommand;
use App\Support\Inventory\OpeningStockIdentity;
use App\Support\Inventory\OpeningStockPlan;
use App\Support\Inventory\OpeningStockVerificationResult;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OpeningStockImportService
{
    public function __construct(
        protected OpeningStockPlanner $planner,
        protected StockLedgerService $ledger,
        protected OpeningStockVerifier $verifier,
    ) {}

    /**
     * @param  array{
     *     warehouse_id?: int|null,
     *     warehouse_code?: string|null,
     *     create_default_warehouse?: bool
     * }  $options
     */
    public function dryRun(array $options = []): OpeningStockPlan
    {
        return $this->planner->plan([
            ...$options,
            'allow_create_warehouse' => false,
        ]);
    }

    /**
     * @param  array{
     *     warehouse_id?: int|null,
     *     warehouse_code?: string|null,
     *     create_default_warehouse?: bool,
     *     created_by?: int|null
     * }  $options
     * @return array{plan: OpeningStockPlan, verification: OpeningStockVerificationResult, posted: int, replayed: int}
     */
    public function execute(array $options = []): array
    {
        $preflight = $this->planner->plan([
            ...$options,
            'allow_create_warehouse' => false,
        ]);

        if ($preflight->negatives !== [] || $preflight->orphans !== []) {
            throw new RuntimeException(implode(' ', $preflight->blockers));
        }

        if (! $preflight->warehouseResolved && ! (bool) ($options['create_default_warehouse'] ?? false)) {
            throw new RuntimeException(implode(' ', $preflight->blockers) ?: __('Warehouse could not be resolved.'));
        }

        if ($preflight->warehouseResolved === false && $preflight->wouldCreateWarehouse === false
            && $preflight->blockers !== []) {
            throw new RuntimeException(implode(' ', $preflight->blockers));
        }

        return DB::transaction(function () use ($options) {
            $plan = $this->planner->plan([
                ...$options,
                'allow_create_warehouse' => (bool) ($options['create_default_warehouse'] ?? false),
            ]);

            if (! $plan->isClean() || $plan->warehouse === null) {
                throw new RuntimeException(implode(' ', $plan->blockers) ?: __('Opening stock preflight failed.'));
            }

            $posted = 0;
            $replayed = 0;

            foreach ($plan->identities as $identity) {
                $result = $this->ledger->post($this->commandFromIdentity($identity, $options['created_by'] ?? null));

                if ($result->replayed) {
                    $replayed++;
                } else {
                    $posted++;
                }
            }

            $verification = $this->verifier->verify([
                'warehouse_id' => $plan->warehouse->id,
            ]);

            if (! $verification->passed) {
                throw new RuntimeException(
                    __('Opening stock verification failed: :failures', [
                        'failures' => implode(' ', $verification->failures),
                    ])
                );
            }

            return [
                'plan' => $plan,
                'verification' => $verification,
                'posted' => $posted,
                'replayed' => $replayed,
            ];
        });
    }

    protected function commandFromIdentity(OpeningStockIdentity $identity, ?int $createdBy): MovementCommand
    {
        return MovementCommand::fromArray([
            'warehouse_id' => $identity->warehouseId,
            'product_id' => $identity->productId,
            'variant_id' => $identity->variantId,
            'quantity' => $identity->quantity,
            'reserved_delta' => 0,
            'movement_type' => StockMovementType::OpeningBalance,
            'idempotency_key' => $identity->idempotencyKey,
            'occurred_at' => now(),
            'reason_code' => 'opening_stock_import',
            'notes' => 'P0.2 opening stock import',
            'created_by' => $createdBy,
            'allow_inactive' => true,
        ]);
    }
}
