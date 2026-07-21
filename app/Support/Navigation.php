<?php

namespace App\Support;

/**
 * Sidebar / quick-link module → permission mapping for @can gates.
 */
class Navigation
{
    /**
     * @return array<string, string> route name prefix/key => permission
     */
    public static function permissionForRoute(string $routeName): ?string
    {
        return match (true) {
            $routeName === 'dashboard' => 'dashboard.read',
            str_starts_with($routeName, 'pos.') => 'pos.read',
            str_starts_with($routeName, 'sale-orders.') => 'sale-orders.read',
            str_starts_with($routeName, 'quotations.') => 'quotations.read',
            str_starts_with($routeName, 'invoices.') => 'invoices.read',
            str_starts_with($routeName, 'purchase-orders.') => 'purchase-orders.read',
            str_starts_with($routeName, 'purchase-requests.') => 'purchase-requests.read',
            str_starts_with($routeName, 'rfqs.') => 'rfqs.read',
            str_starts_with($routeName, 'bills.') => 'bills.read',
            str_starts_with($routeName, 'supplier-evaluations.') => 'supplier-evaluations.read',
            str_starts_with($routeName, 'products.') => 'products.read',
            str_starts_with($routeName, 'variants.') => 'variants.read',
            str_starts_with($routeName, 'price-lists.') => 'price-lists.read',
            str_starts_with($routeName, 'warehouses.') => 'warehouses.read',
            str_starts_with($routeName, 'inventory-adjustments.') => 'inventory-adjustments.read',
            str_starts_with($routeName, 'shipping-methods.') => 'shipping-methods.read',
            str_starts_with($routeName, 'delivery-trips.') => 'delivery-trips.read',
            str_starts_with($routeName, 'stock-transfers.') => 'stock-transfers.read',
            str_starts_with($routeName, 'floor-plans.') => 'floor-plans.read',
            str_starts_with($routeName, 'vehicle-maintenance.') => 'vehicle-maintenance.read',
            str_starts_with($routeName, 'production-orders.') => 'production-orders.read',
            str_starts_with($routeName, 'bill-of-materials.') => 'bill-of-materials.read',
            str_starts_with($routeName, 'quality-controls.') => 'quality-control.read',
            str_starts_with($routeName, 'leads.') => 'leads.read',
            str_starts_with($routeName, 'customer-feedback.') => 'customer-feedback.read',
            str_starts_with($routeName, 'crm-interactions.') => 'crm-interactions.read',
            str_starts_with($routeName, 'employees.') => 'employees.read',
            str_starts_with($routeName, 'payrolls.') => 'payrolls.read',
            str_starts_with($routeName, 'attendance.') => 'attendance.read',
            str_starts_with($routeName, 'leave-requests.') => 'leave-requests.read',
            str_starts_with($routeName, 'shifts.') => 'shift-management.read',
            str_starts_with($routeName, 'performance-reviews.') => 'performance-reviews.read',
            str_starts_with($routeName, 'chart-of-accounts.') => 'chart-of-accounts.read',
            str_starts_with($routeName, 'fiscal-periods.') => 'fiscal-periods.read',
            str_starts_with($routeName, 'currencies.') => 'currencies.read',
            str_starts_with($routeName, 'ledger.') => 'ledgers.read',
            str_starts_with($routeName, 'statements.') => 'financial-statements.read',
            str_starts_with($routeName, 'expenses.') => 'expenses.read',
            str_starts_with($routeName, 'journal-entries.') => 'journal-entries.read',
            str_starts_with($routeName, 'payments.') => 'payments.read',
            str_starts_with($routeName, 'tax-rates.') => 'tax-rates.read',
            str_starts_with($routeName, 'fixed-assets.') => 'fixed-assets.read',
            str_starts_with($routeName, 'budgets.') => 'budgeting.read',
            str_starts_with($routeName, 'bank-reconciliations.') => 'bank-reconciliation.read',
            str_starts_with($routeName, 'financial-reports.') => 'financial-reports.read',
            str_starts_with($routeName, 'contracts.') => 'contracts.read',
            str_starts_with($routeName, 'project-tasks.') => 'project-tasks.read',
            str_starts_with($routeName, 'time-logs.') => 'time-logs.read',
            str_starts_with($routeName, 'campaigns.') => 'campaigns.read',
            str_starts_with($routeName, 'loyalty-programs.') => 'loyalty-programs.read',
            str_starts_with($routeName, 'coupons.') => 'coupons.read',
            str_starts_with($routeName, 'gift-cards.') => 'gift-cards.read',
            str_starts_with($routeName, 'subscriptions.') => 'subscriptions.read',
            str_starts_with($routeName, 'tickets.') => 'tickets.read',
            str_starts_with($routeName, 'knowledge-base.') => 'knowledge-base.read',
            str_starts_with($routeName, 'users.') => 'users.read',
            str_starts_with($routeName, 'branches.') => 'branches.read',
            str_starts_with($routeName, 'notification-templates.') => 'notification-templates.read',
            str_starts_with($routeName, 'notifications.') => 'notifications.read',
            str_starts_with($routeName, 'analytics.') => 'analytics.read',
            str_starts_with($routeName, 'documents.') => 'documents.read',
            str_starts_with($routeName, 'audit-logs.') => 'audit-logs.read',
            str_starts_with($routeName, 'system-information.') => 'settings.read',
            str_starts_with($routeName, 'activities.') => 'activities.read',
            str_starts_with($routeName, 'contacts.') => 'contacts.read',
            default => null,
        };
    }

    public static function canAccessRoute(string $routeName): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $permission = self::permissionForRoute($routeName);

        if ($permission === null) {
            return true;
        }

        return auth()->user()->can($permission);
    }
}
