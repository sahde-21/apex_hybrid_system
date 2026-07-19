<?php

use App\Models\PosSale;
use App\Models\User;
use App\Policies\PosSalePolicy;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->policy = new PosSalePolicy;
    $this->sale = PosSale::factory()->create();
});

it('denies pos abilities without permissions', function () {
    $user = User::factory()->create();

    expect($this->policy->viewAny($user))->toBeFalse()
        ->and($this->policy->view($user, $this->sale))->toBeFalse()
        ->and($this->policy->create($user))->toBeFalse()
        ->and($this->policy->update($user, $this->sale))->toBeFalse()
        ->and($this->policy->delete($user, $this->sale))->toBeFalse()
        ->and($this->policy->print($user, $this->sale))->toBeFalse()
        ->and($this->policy->export($user))->toBeFalse()
        ->and($this->policy->approve($user, $this->sale))->toBeFalse()
        ->and($this->policy->refund($user, $this->sale))->toBeFalse()
        ->and($this->policy->openShift($user))->toBeFalse()
        ->and($this->policy->closeShift($user))->toBeFalse();
});

it('allows pos abilities with matching permissions', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([
        'pos.read',
        'pos.create',
        'pos.update',
        'pos.delete',
        'pos.print',
        'pos.export',
        'pos.approve',
    ]);

    expect($this->policy->viewAny($user))->toBeTrue()
        ->and($this->policy->create($user))->toBeTrue()
        ->and($this->policy->refund($user, $this->sale))->toBeTrue()
        ->and($this->policy->openShift($user))->toBeTrue()
        ->and($this->policy->closeShift($user))->toBeTrue()
        ->and($this->policy->print($user, $this->sale))->toBeTrue();
});

it('allows super-admin through gate before for pos', function () {
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    expect($user->can('viewAny', PosSale::class))->toBeTrue()
        ->and($user->can('create', PosSale::class))->toBeTrue()
        ->and($user->can('print', $this->sale))->toBeTrue()
        ->and($user->can('refund', $this->sale))->toBeTrue();
});
