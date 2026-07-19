<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('super-admin');

    $this->actingAs($user);
});

it('allows authenticated super-admin to visit core ERP index pages', function (string $routeName) {
    $this->get(route($routeName))->assertOk();
})->with([
    'products' => 'products.index',
    'contacts' => 'contacts.index',
    'invoices' => 'invoices.index',
    'payments' => 'payments.index',
    'sale-orders' => 'sale-orders.index',
    'purchase-orders' => 'purchase-orders.index',
    'bills' => 'bills.index',
    'expenses' => 'expenses.index',
    'branches' => 'branches.index',
    'warehouses' => 'warehouses.index',
    'employees' => 'employees.index',
    'tickets' => 'tickets.index',
]);

it('exports products contacts and invoices as csv', function (string $type) {
    $this->get(route('export.csv', ['type' => $type]))
        ->assertOk()
        ->assertHeader('content-disposition');
})->with([
    'products',
    'contacts',
    'invoices',
]);

it('exports products contacts and invoices as excel', function (string $type) {
    $this->get(route('export.excel', ['type' => $type]))
        ->assertOk()
        ->assertHeader('content-disposition');
})->with([
    'products',
    'contacts',
    'invoices',
]);
