<?php

namespace App\Services\Supplier;

use App\Enums\ContractStatus;
use App\Enums\PaymentType;
use App\Models\Bill;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\PortalSupplier;
use App\Models\PurchaseOrder;
use App\Models\SupplierShipment;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class SupplierDashboardService
{
    /**
     * @return array{
     *     purchase_orders: int,
     *     bills: int,
     *     payments: int,
     *     outstanding: float,
     *     contracts: int,
     *     deliveries: int,
     *     unread_notifications: int
     * }
     */
    public function metrics(PortalSupplier $supplier): array
    {
        $contactId = $supplier->contact_id;

        return [
            'purchase_orders' => PurchaseOrder::query()->where('contact_id', $contactId)->count(),
            'bills' => Bill::query()->where('contact_id', $contactId)->count(),
            'payments' => Payment::query()->where('contact_id', $contactId)->where('type', PaymentType::Outgoing)->count(),
            'outstanding' => app(SupplierPortalService::class)->outstandingBalance($contactId),
            'contracts' => Contract::query()
                ->where('contact_id', $contactId)
                ->whereIn('status', [ContractStatus::Active, ContractStatus::Draft])
                ->count(),
            'deliveries' => SupplierShipment::query()->where('contact_id', $contactId)->count(),
            'unread_notifications' => $supplier->portalNotifications()->whereNull('read_at')->count(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function recentActivity(PortalSupplier $supplier, int $limit = 8): Collection
    {
        $contactId = $supplier->contact_id;

        return collect(array_merge(
            $this->purchaseOrderActivity($contactId),
            $this->billActivity($contactId),
            $this->shipmentActivity($contactId),
        ))
            ->sortByDesc('at')
            ->take($limit)
            ->values()
            ->map(fn (array $row): array => $this->normalizeActivityRow($row));
    }

    /**
     * @return array<int, array{type: string, label: string, status: string, at: CarbonInterface, url: string}>
     */
    private function purchaseOrderActivity(int $contactId): array
    {
        return PurchaseOrder::query()
            ->where('contact_id', $contactId)
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (PurchaseOrder $o) => $this->activityRow(
                'purchase_order',
                $o->reference_number,
                $o->status->value,
                $o->created_at,
                route('supplier.purchase-orders.show', $o),
            ))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{type: string, label: string, status: string, at: CarbonInterface, url: string}>
     */
    private function billActivity(int $contactId): array
    {
        return Bill::query()
            ->where('contact_id', $contactId)
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (Bill $b) => $this->activityRow(
                'bill',
                $b->reference_number,
                $b->status->value,
                $b->created_at,
                route('supplier.bills.show', $b),
            ))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{type: string, label: string, status: string, at: CarbonInterface, url: string}>
     */
    private function shipmentActivity(int $contactId): array
    {
        return SupplierShipment::query()
            ->where('contact_id', $contactId)
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (SupplierShipment $s) => $this->activityRow(
                'delivery',
                $s->reference_number,
                $s->status->value,
                $s->created_at,
                route('supplier.deliveries.index'),
            ))
            ->values()
            ->all();
    }

    /**
     * @param  array{type: string, label: string, status: string, at: CarbonInterface, url: string}  $row
     * @return array<string, mixed>
     */
    private function normalizeActivityRow(array $row): array
    {
        return [
            'type' => $row['type'],
            'label' => $row['label'],
            'status' => $row['status'],
            'at' => $row['at'],
            'url' => $row['url'],
        ];
    }

    /**
     * @return array{type: string, label: string, status: string, at: CarbonInterface, url: string}
     */
    private function activityRow(string $type, string $label, string $status, CarbonInterface $at, string $url): array
    {
        return [
            'type' => $type,
            'label' => $label,
            'status' => $status,
            'at' => $at,
            'url' => $url,
        ];
    }
}
