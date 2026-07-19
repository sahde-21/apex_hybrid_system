<?php

use App\Models\Attendance;
use App\Models\Bill;
use App\Models\Campaign;
use App\Models\Contract;
use App\Models\GiftCard;
use App\Models\InventoryAdjustment;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\LoyaltyProgram;
use App\Models\Payment;
use App\Models\ProductionOrder;
use App\Models\ProjectTask;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\SaleOrder;
use App\Models\StockTransfer;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\SupplierShipment;

return [

    /*
    |--------------------------------------------------------------------------
    | Channel toggles
    |--------------------------------------------------------------------------
    | Database is always on. Mail / SMS / Push are architecture stubs until
    | provider credentials and templates are production-ready.
    */
    'channels' => [
        'database' => true,
        'mail' => (bool) env('NOTIFICATIONS_MAIL', false),
        'sms' => (bool) env('NOTIFICATIONS_SMS', false),
        'push' => (bool) env('NOTIFICATIONS_PUSH', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Domain observer
    |--------------------------------------------------------------------------
    */
    'domain_enabled' => (bool) env('NOTIFICATIONS_DOMAIN', true),

    /*
    |--------------------------------------------------------------------------
    | Domain model → staff notification mapping
    |--------------------------------------------------------------------------
    */
    'domain' => [
        SaleOrder::class => [
            'module' => 'sale-orders',
            'permission' => 'sale-orders.read',
            'label' => 'Sale order',
            'route' => 'sale-orders.show',
        ],
        Quotation::class => [
            'module' => 'quotations',
            'permission' => 'quotations.read',
            'label' => 'Quotation',
            'route' => 'quotations.show',
        ],
        Invoice::class => [
            'module' => 'invoices',
            'permission' => 'invoices.read',
            'label' => 'Invoice',
            'route' => 'invoices.show',
        ],
        PurchaseOrder::class => [
            'module' => 'purchase-orders',
            'permission' => 'purchase-orders.read',
            'label' => 'Purchase order',
            'route' => 'purchase-orders.show',
        ],
        Bill::class => [
            'module' => 'bills',
            'permission' => 'bills.read',
            'label' => 'Bill',
            'route' => 'bills.show',
        ],
        Payment::class => [
            'module' => 'payments',
            'permission' => 'payments.read',
            'label' => 'Payment',
            'route' => 'payments.show',
        ],
        InventoryAdjustment::class => [
            'module' => 'inventory-adjustments',
            'permission' => 'inventory-adjustments.read',
            'label' => 'Inventory adjustment',
            'route' => 'inventory-adjustments.show',
        ],
        StockTransfer::class => [
            'module' => 'stock-transfers',
            'permission' => 'stock-transfers.read',
            'label' => 'Stock transfer',
            'route' => 'stock-transfers.show',
        ],
        ProductionOrder::class => [
            'module' => 'production-orders',
            'permission' => 'production-orders.read',
            'label' => 'Production order',
            'route' => 'production-orders.show',
        ],
        Ticket::class => [
            'module' => 'tickets',
            'permission' => 'tickets.read',
            'label' => 'Ticket',
            'route' => 'tickets.show',
        ],
        LeaveRequest::class => [
            'module' => 'leave-requests',
            'permission' => 'leave-requests.read',
            'label' => 'Leave request',
            'route' => 'leave-requests.show',
        ],
        Attendance::class => [
            'module' => 'attendance',
            'permission' => 'attendance.read',
            'label' => 'Attendance',
            'route' => 'attendance.index',
        ],
        ProjectTask::class => [
            'module' => 'project-tasks',
            'permission' => 'project-tasks.read',
            'label' => 'Project task',
            'route' => 'project-tasks.show',
        ],
        Contract::class => [
            'module' => 'contracts',
            'permission' => 'contracts.read',
            'label' => 'Contract',
            'route' => 'contracts.show',
        ],
        Subscription::class => [
            'module' => 'subscriptions',
            'permission' => 'subscriptions.read',
            'label' => 'Subscription',
            'route' => 'subscriptions.show',
        ],
        GiftCard::class => [
            'module' => 'gift-cards',
            'permission' => 'gift-cards.read',
            'label' => 'Gift card',
            'route' => 'gift-cards.show',
        ],
        LoyaltyProgram::class => [
            'module' => 'loyalty-programs',
            'permission' => 'loyalty-programs.read',
            'label' => 'Loyalty program',
            'route' => 'loyalty-programs.index',
        ],
        Campaign::class => [
            'module' => 'campaigns',
            'permission' => 'campaigns.read',
            'label' => 'Campaign',
            'route' => 'campaigns.show',
        ],
        SupplierShipment::class => [
            'module' => 'purchase-orders',
            'permission' => 'purchase-orders.read',
            'label' => 'Supplier shipment',
            'route' => 'purchase-orders.index',
        ],
    ],
];
