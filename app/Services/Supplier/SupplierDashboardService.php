<?php

namespace App\Services\Supplier;

use App\Enums\BillStatus;
use App\Enums\ContractStatus;
use App\Enums\PaymentType;
use App\Enums\PurchaseOrderStatus;
use App\Models\Bill;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\PortalSupplier;
use App\Models\PurchaseOrder;
use App\Models\SupplierShipment;
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

        $orders = PurchaseOrder::query()
            ->where('contact_id', $contactId)
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (PurchaseOrder $o) => [
                'type' => 'purchase_order',
                'label' => $o->reference_number,
                'status' => $o->status instanceof PurchaseOrderStatus ? $o->status->value : (string) $o->status,
                'at' => $o->created_at,
                'url' => route('supplier.purchase-orders.show', $o),
            ]);

        $bills = Bill::query()
            ->where('contact_id', $contactId)
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (Bill $b) => [
                'type' => 'bill',
                'label' => $b->reference_number,
                'status' => $b->status instanceof BillStatus ? $b->status->value : (string) $b->status,
                'at' => $b->created_at,
                'url' => route('supplier.bills.show', $b),
            ]);

        $shipments = SupplierShipment::query()
            ->where('contact_id', $contactId)
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (SupplierShipment $s) => [
                'type' => 'delivery',
                'label' => $s->reference_number,
                'status' => $s->status->value,
                'at' => $s->created_at,
                'url' => route('supplier.deliveries.index'),
            ]);

        return $orders
            ->concat($bills)
            ->concat($shipments)
            ->sortByDesc('at')
            ->take($limit)
            ->values();
    }
}
