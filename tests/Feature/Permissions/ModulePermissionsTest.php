<?php

use App\Models\User;
use App\Support\ModulePermissions;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('seeds every module action permission', function () {
    $expected = ModulePermissions::allPermissions();

    expect(Permission::query()->where('guard_name', 'web')->pluck('name')->all())
        ->toEqualCanonicalizing($expected);
});

it('creates all enterprise roles', function () {
    foreach (ModulePermissions::ROLES as $role) {
        expect(Role::findByName($role, 'web'))->not->toBeNull();
    }
});

it('gives super-admin every permission', function () {
    $user = User::factory()->create();
    $user->assignRole('super-admin');

    foreach (['users.read', 'products.delete', 'invoices.approve', 'settings.update'] as $permission) {
        expect($user->can($permission))->toBeTrue();
    }
});

it('builds permission names through helper', function () {
    expect(ModulePermissions::permissionName('products', 'export'))->toBe('products.export');
});
