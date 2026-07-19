<?php

use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('permissionless authenticated users cannot export data', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('export.csv', ['type' => 'products']))->assertForbidden();
    $this->get(route('export.excel', ['type' => 'contacts']))->assertForbidden();
});

test('users without export permission cannot export invoices', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([
        'dashboard.read',
        'invoices.read',
        'invoices.create',
        'invoices.update',
        'invoices.print',
    ]);
    $this->actingAs($user);

    $this->get(route('export.csv', ['type' => 'invoices']))->assertForbidden();
});

test('cashiers with export permission can export invoices', function () {
    $user = User::factory()->create();
    $user->assignRole('cashier');
    $this->actingAs($user);

    $this->get(route('export.csv', ['type' => 'invoices']))
        ->assertOk()
        ->assertHeader('content-disposition');
});

test('permissionless users cannot print invoices', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->create();
    $this->actingAs($user);

    $this->get(route('print.document', ['type' => 'invoice', 'id' => $invoice->id]))
        ->assertForbidden();
});

test('users with print permission can print invoices', function () {
    $user = User::factory()->create();
    $user->assignRole('sales');
    $invoice = Invoice::factory()->create();
    $this->actingAs($user);

    $this->get(route('print.document', ['type' => 'invoice', 'id' => $invoice->id]))
        ->assertOk();
});

test('users without users.approve cannot escalate to super-admin via update', function () {
    $actor = User::factory()->create();
    $actor->givePermissionTo(['users.read', 'users.update', 'users.create']);
    $target = User::factory()->create();

    $this->actingAs($actor);

    $this->put(route('users.update', $target), [
        'name' => $target->name,
        'email' => $target->email,
        'phone' => $target->phone,
        'roles' => ['super-admin'],
        'permissions' => [],
    ])->assertRedirect(route('users.index'));

    expect($target->fresh()->hasRole('super-admin'))->toBeFalse();
});

test('dashboard requires dashboard.read permission', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))->assertForbidden();

    $user->givePermissionTo('dashboard.read');
    $this->get(route('dashboard'))->assertOk();
});

test('security headers are present on web responses', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('dashboard.read');
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

test('avatar uploads reject non-image payloads', function () {
    Storage::fake('public');

    $actor = User::factory()->create();
    $actor->assignRole('super-admin');
    $this->actingAs($actor);

    $this->post(route('users.store'), [
        'name' => 'Avatar User',
        'email' => 'avatar-user@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'is_active' => true,
        'roles' => ['cashier'],
        'avatar' => UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream'),
    ])->assertSessionHasErrors('avatar');
});

test('api docs remain available in local and health is public', function () {
    $this->getJson('/api/v1/health')->assertOk();
    $this->getJson('/api/v1/docs')->assertOk();
});
