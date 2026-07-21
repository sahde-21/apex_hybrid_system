<?php

namespace App\Support;

class ModulePermissions
{
    /** @var array<int, string> */
    public const ACTIONS = [
        'read',
        'create',
        'update',
        'delete',
        'approve',
        'export',
        'print',
    ];

    /** @var array<int, string> */
    public const ROLES = [
        'super-admin',
        'owner',
        'manager',
        'cashier',
        'warehouse',
        'hr',
        'accountant',
        'sales',
        'purchasing',
        'customer-support',
    ];

    /** @var array<int, string> */
    public const MODULES = [
        'dashboard',
        'products',
        'warehouses',
        'inventory-adjustments',
        'stock-transfers',
        'variants',
        'price-lists',
        'purchase-orders',
        'purchase-requests',
        'rfqs',
        'bills',
        'supplier-evaluations',
        'sale-orders',
        'quotations',
        'invoices',
        'pos',
        'expenses',
        'journal-entries',
        'payments',
        'tax-rates',
        'financial-reports',
        'fixed-assets',
        'budgeting',
        'bank-reconciliation',
        'employees',
        'payrolls',
        'attendance',
        'leave-requests',
        'shift-management',
        'performance-reviews',
        'crm-interactions',
        'leads',
        'customer-feedback',
        'campaigns',
        'contacts',
        'production-orders',
        'bill-of-materials',
        'quality-control',
        'shipping-methods',
        'delivery-trips',
        'vehicle-maintenance',
        'branches',
        'floor-plans',
        'loyalty-programs',
        'coupons',
        'gift-cards',
        'subscriptions',
        'contracts',
        'project-tasks',
        'time-logs',
        'tickets',
        'knowledge-base',
        'audit-logs',
        'notification-templates',
        'notifications',
        'analytics',
        'documents',
        'chart-of-accounts',
        'ledgers',
        'financial-statements',
        'fiscal-periods',
        'currencies',
        'users',
        'settings',
        'activities',
    ];

    /** @var array<int, string> */
    public const EXTRA_PERMISSIONS = [
        'journal-entries.post',
        'journal-entries.reverse',
        'fiscal-periods.manage',
        'quotations.send',
        'quotations.reject',
        'quotations.convert',
        'sale-orders.submit',
        'sale-orders.confirm',
        'sale-orders.invoice',
        'invoices.issue',
        'invoices.void',
        'payments.record',
        'payments.post',
        'payments.reverse',
        'purchase-requests.submit',
        'purchase-requests.convert',
        'rfqs.send',
        'rfqs.accept',
        'purchase-orders.submit',
        'purchase-orders.confirm',
        'purchase-orders.bill',
        'bills.issue',
        'bills.void',
        'workflow.submit',
        'workflow.approve',
        'workflow.reject',
        'workflow.cancel',
        'workflow.reopen',
        'workflow.archive',
        'activities.view_all',
        'activities.comment',
        'activities.internal_note',
        'activities.edit_own',
        'activities.delete_own',
        'activities.manage',
    ];

    /**
     * @return array<int, string>
     */
    public static function allPermissions(): array
    {
        $permissions = [];

        foreach (self::MODULES as $module) {
            foreach (self::ACTIONS as $action) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        return array_values(array_unique([...$permissions, ...self::EXTRA_PERMISSIONS]));
    }

    public static function permissionName(string $module, string $action): string
    {
        return "{$module}.{$action}";
    }
}
