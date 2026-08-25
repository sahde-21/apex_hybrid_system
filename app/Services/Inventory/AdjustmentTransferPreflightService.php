<?php

namespace App\Services\Inventory;

use App\Enums\InventoryAdjustmentReason;
use App\Enums\InventoryAdjustmentStatus;
use App\Enums\StockTransferStatus;
use App\Models\InventoryAdjustment;
use App\Models\StockTransfer;
use App\Support\Inventory\AdjustmentTransferPreflightResult;

class AdjustmentTransferPreflightService
{
    /**
     * Read-only readiness for P0.4 adjustment/transfer ledger cutover.
     * Never enables inventory.ledger_enabled.
     */
    public function check(): AdjustmentTransferPreflightResult
    {
        $failures = [];
        $warnings = [];

        $missingWarehouse = InventoryAdjustment::query()
            ->whereNull('warehouse_id')
            ->whereIn('status', [
                InventoryAdjustmentStatus::Draft->value,
                InventoryAdjustmentStatus::Approved->value,
            ])
            ->count();

        if ($missingWarehouse > 0) {
            $failures[] = __(':count open adjustment(s) have no warehouse_id and cannot be posted until assigned.', [
                'count' => $missingWarehouse,
            ]);
        }

        $invalidReason = 0;
        InventoryAdjustment::query()
            ->whereIn('status', [
                InventoryAdjustmentStatus::Draft->value,
                InventoryAdjustmentStatus::Approved->value,
            ])
            ->orderBy('id')
            ->chunkById(100, function ($rows) use (&$invalidReason): void {
                foreach ($rows as $row) {
                    if (InventoryAdjustmentReason::tryFrom($row->reason) === null) {
                        $invalidReason++;
                    }
                }
            });

        if ($invalidReason > 0) {
            $failures[] = __(':count open adjustment(s) have a reason that is not a supported enum code.', [
                'count' => $invalidReason,
            ]);
        }

        $sameWarehouse = StockTransfer::query()
            ->whereColumn('from_warehouse_id', 'to_warehouse_id')
            ->whereNot('status', StockTransferStatus::Cancelled->value)
            ->count();

        if ($sameWarehouse > 0) {
            $failures[] = __(':count transfer(s) have the same source and destination warehouse.', [
                'count' => $sameWarehouse,
            ]);
        }

        $inTransit = StockTransfer::query()
            ->where('status', StockTransferStatus::InTransit->value)
            ->count();

        if ($inTransit > 0) {
            $warnings[] = __(':count transfer(s) are currently in transit.', ['count' => $inTransit]);
        }

        if ((bool) config('inventory.ledger_enabled', false)) {
            $warnings[] = __('inventory.ledger_enabled is already true. Preflight is informational only.');
        } else {
            $warnings[] = __('inventory.ledger_enabled remains false. Enable only after explicit approval.');
        }

        $passed = $failures === [];

        return new AdjustmentTransferPreflightResult(
            passed: $passed,
            ledgerEnabled: (bool) config('inventory.ledger_enabled', false),
            adjustmentsMissingWarehouse: $missingWarehouse,
            adjustmentsInvalidReason: $invalidReason,
            transfersSameWarehouse: $sameWarehouse,
            inTransitCount: $inTransit,
            failures: $failures,
            warnings: $warnings,
        );
    }
}
