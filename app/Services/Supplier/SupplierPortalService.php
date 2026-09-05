<?php

namespace App\Services\Supplier;

use App\Enums\BillStatus;
use App\Enums\NotificationCategory;
use App\Enums\NotificationPriority;
use App\Enums\PaymentType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\SupplierResponseStatus;
use App\Enums\SupplierShipmentStatus;
use App\Models\Bill;
use App\Models\Contact;
use App\Models\Payment;
use App\Models\PortalSupplier;
use App\Models\PortalSupplierNotification;
use App\Models\PurchaseOrder;
use App\Models\SupplierShipment;
use App\Services\Notifications\NotificationCenterService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class SupplierPortalService
{
    public function notify(PortalSupplier $supplier, string $type, string $title, ?string $body = null, ?string $actionUrl = null): PortalSupplierNotification
    {
        return $supplier->portalNotifications()->create([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'action_url' => $actionUrl,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(PortalSupplier $supplier, array $data, ?UploadedFile $avatar = null): PortalSupplier
    {
        return DB::transaction(function () use ($supplier, $data, $avatar) {
            if ($avatar) {
                $data['avatar_path'] = $avatar->store('supplier-avatars', 'public');
            }

            $supplier->update(collect($data)->only([
                'name', 'phone', 'email', 'locale', 'password', 'avatar_path',
            ])->filter(fn ($v) => $v !== null)->all());

            /** @var Contact $contact */
            $contact = $supplier->contact;
            $contact->update([
                'name' => $supplier->name,
                'company_name' => $data['company_name'] ?? $contact->company_name,
                'phone' => $supplier->phone,
                'email' => $supplier->email,
                'address' => $data['address'] ?? $contact->address,
            ]);

            return $supplier->fresh(['contact']);
        });
    }

    public function acceptPurchaseOrder(PurchaseOrder $order, PortalSupplier $supplier, ?string $comment = null): PurchaseOrder
    {
        abort_unless((int) $order->contact_id === (int) $supplier->contact_id, 403);
        abort_unless(in_array($order->status->value, ['confirmed', 'draft'], true), 422);

        if ($order->supplier_response === SupplierResponseStatus::Accepted) {
            return $order;
        }

        $order->update([
            'supplier_response' => SupplierResponseStatus::Accepted,
            'supplier_comment' => $comment,
            'supplier_responded_at' => now(),
            'status' => PurchaseOrderStatus::Confirmed,
        ]);

        $this->notify(
            $supplier,
            'purchase_order.accepted',
            __('scf.supplier_portal.po_accepted'),
            $order->reference_number,
            route('supplier.purchase-orders.show', $order),
        );

        app(NotificationCenterService::class)->notifyByPermission(
            permission: 'purchase-orders.read',
            event: 'supplier.purchase_order.accepted',
            title: __('Supplier accepted PO :ref', ['ref' => $order->reference_number]),
            body: $supplier->name,
            category: NotificationCategory::Success,
            priority: NotificationPriority::High,
            module: 'purchase-orders',
            actionUrl: Route::has('purchase-orders.show') ? route('purchase-orders.show', $order) : null,
        );

        return $order->fresh();
    }

    public function rejectPurchaseOrder(PurchaseOrder $order, PortalSupplier $supplier, ?string $comment = null): PurchaseOrder
    {
        abort_unless((int) $order->contact_id === (int) $supplier->contact_id, 403);
        abort_unless(in_array($order->status->value, ['confirmed', 'draft'], true), 422);

        $order->update([
            'supplier_response' => SupplierResponseStatus::Rejected,
            'supplier_comment' => $comment,
            'supplier_responded_at' => now(),
            'status' => PurchaseOrderStatus::Cancelled,
        ]);

        $this->notify(
            $supplier,
            'purchase_order.rejected',
            __('scf.supplier_portal.po_rejected'),
            $order->reference_number,
            route('supplier.purchase-orders.show', $order),
        );

        app(NotificationCenterService::class)->notifyByPermission(
            permission: 'purchase-orders.read',
            event: 'supplier.purchase_order.rejected',
            title: __('Supplier rejected PO :ref', ['ref' => $order->reference_number]),
            body: $supplier->name.($comment ? ' — '.$comment : ''),
            category: NotificationCategory::Warning,
            priority: NotificationPriority::High,
            module: 'purchase-orders',
            actionUrl: Route::has('purchase-orders.show') ? route('purchase-orders.show', $order) : null,
        );

        return $order->fresh();
    }

    public function commentOnPurchaseOrder(PurchaseOrder $order, PortalSupplier $supplier, string $comment): PurchaseOrder
    {
        abort_unless((int) $order->contact_id === (int) $supplier->contact_id, 403);

        $order->update([
            'supplier_comment' => $comment,
        ]);

        $this->notify(
            $supplier,
            'purchase_order.comment',
            __('scf.supplier_portal.po_commented'),
            $order->reference_number,
            route('supplier.purchase-orders.show', $order),
        );

        return $order->fresh();
    }

    /**
     * @param  array{carrier?: string, tracking_number?: string, notes?: string, scheduled_date?: string}  $data
     */
    public function confirmShipment(PurchaseOrder $order, PortalSupplier $supplier, array $data = []): SupplierShipment
    {
        abort_unless((int) $order->contact_id === (int) $supplier->contact_id, 403);

        return DB::transaction(function () use ($order, $supplier, $data) {
            $shipment = SupplierShipment::query()->create([
                'reference_number' => 'SHP-'.strtoupper(Str::random(8)),
                'contact_id' => $supplier->contact_id,
                'purchase_order_id' => $order->id,
                'scheduled_date' => $data['scheduled_date'] ?? $order->expected_date?->toDateString() ?? now()->toDateString(),
                'shipped_at' => now(),
                'carrier' => $data['carrier'] ?? null,
                'tracking_number' => $data['tracking_number'] ?? null,
                'status' => SupplierShipmentStatus::Shipped,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->notify(
                $supplier,
                'shipment.confirmed',
                __('scf.supplier_portal.shipment_confirmed'),
                $shipment->reference_number,
                route('supplier.deliveries.index'),
            );

            app(NotificationCenterService::class)->notifyByPermission(
                permission: 'purchase-orders.read',
                event: 'supplier.shipment.confirmed',
                title: __('Supplier shipment confirmed: :ref', ['ref' => $shipment->reference_number]),
                body: $order->reference_number.($shipment->tracking_number ? ' · '.$shipment->tracking_number : ''),
                category: NotificationCategory::Success,
                priority: NotificationPriority::Medium,
                module: 'purchase-orders',
                actionUrl: Route::has('purchase-orders.show') ? route('purchase-orders.show', $order) : null,
            );

            return $shipment;
        });
    }

    public function outstandingBalance(int $contactId): float
    {
        $bills = (float) Bill::query()
            ->where('contact_id', $contactId)
            ->whereIn('status', [BillStatus::Received, BillStatus::Overdue])
            ->sum('total_amount');

        $paid = (float) Payment::query()
            ->where('contact_id', $contactId)
            ->where('type', PaymentType::Outgoing)
            ->sum('amount');

        return max(0, $bills - $paid);
    }
}
