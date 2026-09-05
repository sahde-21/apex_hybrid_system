<?php

namespace App\Services\Inventory;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReturn;
use App\Models\Warehouse;
use App\Support\Inventory\PurchaseLedgerPreflightResult;

class PurchaseLedgerPreflightService
{
    /**
     * Read-only readiness for P0.5 purchase receipt/return ledger cutover.
     * Never enables inventory.ledger_enabled and never mutates data.
     */
    public function check(): PurchaseLedgerPreflightResult
    {
        $failures = [];
        $warnings = [];

        $receivableStatuses = [
            PurchaseOrderStatus::Confirmed->value,
            PurchaseOrderStatus::Received->value,
            PurchaseOrderStatus::Approved->value,
            PurchaseOrderStatus::PartiallyBilled->value,
            PurchaseOrderStatus::FullyBilled->value,
        ];

        $missingWarehouse = PurchaseOrder::query()
            ->whereIn('status', $receivableStatuses)
            ->whereNull('warehouse_id')
            ->count();

        if ($missingWarehouse > 0) {
            $failures[] = __(':count receivable purchase order(s) have no warehouse_id.', [
                'count' => $missingWarehouse,
            ]);
        }

        $inactiveWarehouse = 0;
        PurchaseOrder::query()
            ->whereIn('status', $receivableStatuses)
            ->whereNotNull('warehouse_id')
            ->orderBy('id')
            ->chunkById(100, function ($orders) use (&$inactiveWarehouse): void {
                $warehouseIds = $orders->pluck('warehouse_id')->unique()->filter()->all();
                $warehouses = Warehouse::query()->whereIn('id', $warehouseIds)->get()->keyBy('id');

                foreach ($orders as $order) {
                    $warehouse = $warehouses->get($order->warehouse_id);
                    if ($warehouse === null || ! $warehouse->is_active) {
                        $inactiveWarehouse++;
                    }
                }
            });

        if ($inactiveWarehouse > 0) {
            $failures[] = __(':count receivable purchase order(s) reference a missing or inactive warehouse.', [
                'count' => $inactiveWarehouse,
            ]);
        }

        $partialReceipts = 0;
        PurchaseOrder::query()
            ->whereIn('status', $receivableStatuses)
            ->with('lines')
            ->orderBy('id')
            ->chunkById(50, function ($orders) use (&$partialReceipts): void {
                foreach ($orders as $order) {
                    $hasPartial = $order->lines->contains(function (PurchaseOrderLine $line): bool {
                        $received = (float) $line->quantity_received;

                        return $received > 0.0001 && $received + 0.0001 < (float) $line->quantity;
                    });

                    if ($hasPartial) {
                        $partialReceipts++;
                    }
                }
            });

        if ($partialReceipts > 0) {
            $warnings[] = __(':count receivable purchase order(s) already have partial receipts recorded.', [
                'count' => $partialReceipts,
            ]);
        }

        $receiptCount = PurchaseReceipt::query()->count();
        $returnCount = PurchaseReturn::query()->count();

        if ((bool) config('inventory.ledger_enabled', false)) {
            $warnings[] = __('inventory.ledger_enabled is already true. Preflight is informational only.');
        } else {
            $warnings[] = __('inventory.ledger_enabled remains false. Enable only after explicit approval.');
        }

        $passed = $failures === [];

        return new PurchaseLedgerPreflightResult(
            passed: $passed,
            ledgerEnabled: (bool) config('inventory.ledger_enabled', false),
            receivableOrdersMissingWarehouse: $missingWarehouse,
            openOrdersWithPartialReceipts: $partialReceipts,
            receiptCount: $receiptCount,
            returnCount: $returnCount,
            failures: $failures,
            warnings: $warnings,
        );
    }
}
