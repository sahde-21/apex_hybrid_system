<?php

use App\Models\Product;
use App\Models\User;
use App\Policies\ProductPolicy;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->policy = new ProductPolicy;
    $this->product = Product::factory()->create();
});

it('requires products permissions for each product policy ability', function () {
    $user = User::factory()->create();

    expect($this->policy->viewAny($user))->toBeFalse();

    $user->givePermissionTo(['products.read', 'products.export', 'products.print']);

    expect($this->policy->viewAny($user))->toBeTrue()
        ->and($this->policy->view($user, $this->product))->toBeTrue()
        ->and($this->policy->export($user))->toBeTrue()
        ->and($this->policy->print($user, $this->product))->toBeTrue()
        ->and($this->policy->create($user))->toBeFalse();
});
