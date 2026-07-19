<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Unit');

expect()->extend('toBeOne', function () {
    return expect($this->value)->toBe(1);
});

/**
 * Seed permissions/roles and authenticate as a super-admin.
 */
function actingAsSuperAdmin(?User $user = null): User
{
    test()->seed(RolePermissionSeeder::class);

    $user ??= User::factory()->create();
    $user->syncRoles(['super-admin']);

    test()->actingAs($user);

    return $user;
}

/**
 * Seed permissions/roles and authenticate as a named role.
 */
function actingAsRole(string $role, ?User $user = null): User
{
    test()->seed(RolePermissionSeeder::class);

    $user ??= User::factory()->create();
    $user->syncRoles([$role]);

    test()->actingAs($user);

    return $user;
}

/**
 * Seed permissions/roles and authenticate with explicit permissions (no role).
 *
 * @param  list<string>  $permissions
 */
function actingAsUserWithPermissions(array $permissions, ?User $user = null): User
{
    test()->seed(RolePermissionSeeder::class);

    $user ??= User::factory()->create();
    $user->syncRoles([]);
    $user->syncPermissions($permissions);

    test()->actingAs($user);

    return $user;
}

/**
 * Authenticate as a portal customer on the portal guard.
 */
function actingAsPortalCustomer(?\App\Models\PortalCustomer $customer = null): \App\Models\PortalCustomer
{
    $customer ??= \App\Models\PortalCustomer::factory()->create();
    test()->actingAs($customer, 'portal');

    return $customer;
}

/**
 * Authenticate as a portal supplier on the supplier guard.
 */
function actingAsPortalSupplier(?\App\Models\PortalSupplier $supplier = null): \App\Models\PortalSupplier
{
    $supplier ??= \App\Models\PortalSupplier::factory()->create();
    test()->actingAs($supplier, 'supplier');

    return $supplier;
}
