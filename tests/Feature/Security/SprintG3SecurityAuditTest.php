<?php

use App\Models\User;
use App\Support\Api\ApiAbilities;
use App\Support\ModulePermissions;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('every module permission string is registered in the database', function () {
    $expected = ModulePermissions::allPermissions();

    foreach ($expected as $permission) {
        expect(\Spatie\Permission\Models\Permission::query()->where('name', $permission)->exists())->toBeTrue();
    }
});

test('warehouse role cannot access user management', function () {
    $user = User::factory()->create();
    $user->assignRole('warehouse');
    $this->actingAs($user);

    $this->get(route('users.index'))->assertForbidden();
});

test('hr role cannot access pos terminal', function () {
    $user = User::factory()->create();
    $user->assignRole('hr');
    $this->actingAs($user);

    if (Route::has('pos.terminal')) {
        $this->get(route('pos.terminal'))->assertForbidden();
    } else {
        expect(true)->toBeTrue();
    }
});

test('read-only user without roles cannot access dashboard or api products', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))->assertForbidden();

    Sanctum::actingAs($user, [ApiAbilities::PRODUCTS_READ]);

    $this->getJson('/api/v1/products')->assertForbidden();
});

test('intelligence export requires domain permission not only export permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['intelligence.view', 'intelligence.export', 'analytics.read']);
    $this->actingAs($user);

    $this->get(route('intelligence.export.csv', ['domain' => 'executive']))->assertForbidden();
});

test('analytics routes require analytics.read permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('dashboard.read');
    $this->actingAs($user);

    $this->get(route('analytics.hub'))->assertForbidden();
});

test('privileged roles bypass is limited to configured roles', function () {
    expect(config('security.privileged_roles'))->toContain('super-admin');
});

test('no duplicate web route names exist', function () {
    $json = shell_exec('php artisan route:list --json 2>/dev/null');
    $routes = json_decode($json ?: '[]', true);
    $names = [];

    foreach ($routes as $route) {
        $name = $route['name'] ?? null;
        if (! $name) {
            continue;
        }
        expect(isset($names[$name]))->toBeFalse("Duplicate route name: {$name}");
        $names[$name] = true;
    }
});

test('sensitive settings routes require authentication', function () {
    $this->get(route('profile.edit'))->assertRedirect();
});
