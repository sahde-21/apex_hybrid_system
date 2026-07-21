<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create([
        'email' => 'admin-'.uniqid('', true).'@scf.com',
    ]);
    $user->syncRoles(['super-admin']);

    $this->actingAs($user);
});

it('allows super-admin to visit every ERP module index', function (string $routeName) {
    $this->get(route($routeName))->assertOk();
})->with([
    'products.index',
    'variants.index',
    'price-lists.index',
    'warehouses.index',
    'inventory-adjustments.index',
    'stock-transfers.index',
    'purchase-orders.index',
    'bills.index',
    'supplier-evaluations.index',
    'sale-orders.index',
    'quotations.index',
    'invoices.index',
    'expenses.index',
    'journal-entries.index',
    'payments.index',
    'tax-rates.index',
    'financial-reports.index',
    'fixed-assets.index',
    'budgets.index',
    'bank-reconciliations.index',
    'employees.index',
    'payrolls.index',
    'attendance.index',
    'leave-requests.index',
    'shifts.index',
    'performance-reviews.index',
    'crm-interactions.index',
    'leads.index',
    'customer-feedback.index',
    'campaigns.index',
    'contacts.index',
    'production-orders.index',
    'bill-of-materials.index',
    'quality-controls.index',
    'shipping-methods.index',
    'delivery-trips.index',
    'vehicle-maintenance.index',
    'branches.index',
    'floor-plans.index',
    'loyalty-programs.index',
    'coupons.index',
    'gift-cards.index',
    'subscriptions.index',
    'contracts.index',
    'project-tasks.index',
    'time-logs.index',
    'tickets.index',
    'knowledge-base.index',
    'audit-logs.index',
    'activities.index',
    'notification-templates.index',
]);

it('grants super-admin all critical permissions', function () {
    $user = auth()->user();

    expect($user->hasRole('super-admin'))->toBeTrue()
        ->and($user->can('products.read'))->toBeTrue()
        ->and($user->can('branches.delete'))->toBeTrue()
        ->and($user->can('customer-feedback.read'))->toBeTrue()
        ->and($user->can('budgeting.read'))->toBeTrue()
        ->and($user->can('shift-management.read'))->toBeTrue();
});
