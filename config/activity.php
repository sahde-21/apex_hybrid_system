<?php

use App\Models\Bill;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Quotation;
use App\Models\Rfq;
use App\Models\SaleOrder;

return [

    'per_page' => 15,

    'source_fetch_multiplier' => 3,

    'ignored_audit_fields' => [
        'updated_at',
        'created_at',
        'deleted_at',
        'remember_token',
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'created_by',
        'updated_by',
    ],

    'tracked_fields' => [
        Quotation::class => [
            'contact_id' => 'customer',
            'status' => 'status',
            'currency_code' => 'currency',
            'total_amount' => 'total',
            'valid_until' => 'due_date',
            'salesperson_id' => 'assigned_user',
        ],
        SaleOrder::class => [
            'contact_id' => 'customer',
            'status' => 'status',
            'currency_code' => 'currency',
            'total_amount' => 'total',
            'salesperson_id' => 'assigned_user',
            'warehouse_id' => 'warehouse',
        ],
        Invoice::class => [
            'contact_id' => 'customer',
            'status' => 'status',
            'currency_code' => 'currency',
            'total_amount' => 'total',
            'due_date' => 'due_date',
            'payment_status' => 'payment_status',
        ],
        Payment::class => [
            'contact_id' => 'customer',
            'status' => 'status',
            'currency_code' => 'currency',
            'amount' => 'total',
            'invoice_id' => 'invoice',
            'bill_id' => 'bill',
        ],
        PurchaseRequest::class => [
            'status' => 'status',
            'department' => 'department',
            'needed_by' => 'due_date',
            'requester_id' => 'assigned_user',
            'currency_code' => 'currency',
            'total_amount' => 'total',
        ],
        Rfq::class => [
            'status' => 'status',
            'selected_vendor_id' => 'vendor',
            'currency_code' => 'currency',
            'total_amount' => 'total',
        ],
        PurchaseOrder::class => [
            'contact_id' => 'vendor',
            'status' => 'status',
            'currency_code' => 'currency',
            'total_amount' => 'total',
            'expected_delivery' => 'due_date',
            'buyer_id' => 'assigned_user',
            'warehouse_id' => 'warehouse',
        ],
        Bill::class => [
            'contact_id' => 'vendor',
            'status' => 'status',
            'currency_code' => 'currency',
            'total_amount' => 'total',
            'due_date' => 'due_date',
            'payment_status' => 'payment_status',
        ],
    ],

    'subject_routes' => [
        Quotation::class => 'quotations.show',
        SaleOrder::class => 'sale-orders.show',
        Invoice::class => 'invoices.show',
        Payment::class => 'payments.show',
        PurchaseRequest::class => 'purchase-requests.show',
        Rfq::class => 'rfqs.show',
        PurchaseOrder::class => 'purchase-orders.show',
        Bill::class => 'bills.show',
    ],
];
