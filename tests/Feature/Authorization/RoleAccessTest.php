<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('allows sales role to access sales modules and forbids purchasing', function () {
    actingAsRole('sales');

    $this->get(route('sale-orders.index'))->assertOk();
    $this->get(route('invoices.index'))->assertOk();
    $this->get(route('purchase-orders.index'))->assertForbidden();
    $this->get(route('users.index'))->assertForbidden();
});

it('allows purchasing role to access purchase modules and forbids hr', function () {
    actingAsRole('purchasing');

    $this->get(route('purchase-orders.index'))->assertOk();
    $this->get(route('bills.index'))->assertOk();
    $this->get(route('employees.index'))->assertForbidden();
});

it('allows warehouse role to access inventory and forbids accounting expenses', function () {
    actingAsRole('warehouse');

    $this->get(route('products.index'))->assertOk();
    $this->get(route('warehouses.index'))->assertOk();
    $this->get(route('expenses.index'))->assertForbidden();
});

it('allows hr role to access employees and forbids products', function () {
    actingAsRole('hr');

    $this->get(route('employees.index'))->assertOk();
    $this->get(route('payrolls.index'))->assertOk();
    $this->get(route('products.index'))->assertForbidden();
});

it('allows accountant role to access expenses and payments', function () {
    actingAsRole('accountant');

    $this->get(route('expenses.index'))->assertOk();
    $this->get(route('payments.index'))->assertOk();
    $this->get(route('employees.index'))->assertForbidden();
});

it('grants cashier print and export on invoices', function () {
    $user = actingAsRole('cashier');

    expect($user->can('invoices.print'))->toBeTrue()
        ->and($user->can('invoices.export'))->toBeTrue()
        ->and($user->can('invoices.delete'))->toBeFalse();
});

it('prevents managers from managing users and settings permissions', function () {
    $user = actingAsRole('manager');

    expect($user->can('users.read'))->toBeFalse()
        ->and($user->can('settings.read'))->toBeFalse()
        ->and($user->can('dashboard.read'))->toBeTrue()
        ->and($user->can('products.read'))->toBeTrue();
});
