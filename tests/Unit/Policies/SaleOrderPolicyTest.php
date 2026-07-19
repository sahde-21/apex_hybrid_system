<?php

use App\Models\SaleOrder;
use App\Models\User;
use App\Policies\SaleOrderPolicy;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->policy = new SaleOrderPolicy;
    $this->saleOrder = SaleOrder::factory()->create();
});

it('authorizes sale order abilities from spatie permissions', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([
        'sale-orders.read',
        'sale-orders.create',
        'sale-orders.update',
        'sale-orders.delete',
    ]);

    expect($this->policy->viewAny($user))->toBeTrue()
        ->and($this->policy->view($user, $this->saleOrder))->toBeTrue()
        ->and($this->policy->create($user))->toBeTrue()
        ->and($this->policy->update($user, $this->saleOrder))->toBeTrue()
        ->and($this->policy->delete($user, $this->saleOrder))->toBeTrue();
});
