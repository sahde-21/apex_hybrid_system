<?php

use App\Models\Invoice;
use App\Models\User;
use App\Policies\InvoicePolicy;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->policy = new InvoicePolicy;
    $this->invoice = Invoice::factory()->create();
});

it('denies invoice abilities without permissions', function () {
    $user = User::factory()->create();

    expect($this->policy->viewAny($user))->toBeFalse()
        ->and($this->policy->view($user, $this->invoice))->toBeFalse()
        ->and($this->policy->create($user))->toBeFalse()
        ->and($this->policy->update($user, $this->invoice))->toBeFalse()
        ->and($this->policy->delete($user, $this->invoice))->toBeFalse()
        ->and($this->policy->print($user, $this->invoice))->toBeFalse()
        ->and($this->policy->export($user))->toBeFalse()
        ->and($this->policy->approve($user, $this->invoice))->toBeFalse();
});

it('allows invoice CRUD print export and approve with matching permissions', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([
        'invoices.read',
        'invoices.create',
        'invoices.update',
        'invoices.delete',
        'invoices.print',
        'invoices.export',
        'invoices.approve',
    ]);

    expect($this->policy->viewAny($user))->toBeTrue()
        ->and($this->policy->view($user, $this->invoice))->toBeTrue()
        ->and($this->policy->create($user))->toBeTrue()
        ->and($this->policy->update($user, $this->invoice))->toBeTrue()
        ->and($this->policy->delete($user, $this->invoice))->toBeTrue()
        ->and($this->policy->print($user, $this->invoice))->toBeTrue()
        ->and($this->policy->export($user))->toBeTrue()
        ->and($this->policy->approve($user, $this->invoice))->toBeTrue();
});

it('allows super-admin through gate before for invoice policy abilities', function () {
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    expect($user->can('viewAny', Invoice::class))->toBeTrue()
        ->and($user->can('print', $this->invoice))->toBeTrue()
        ->and($user->can('export', Invoice::class))->toBeTrue();
});
