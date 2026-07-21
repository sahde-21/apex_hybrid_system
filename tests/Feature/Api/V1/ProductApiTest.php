<?php

use App\Models\Contact;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('api returns request id header and meta', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/auth/me', ['X-Request-Id' => 'test-request-123'])
        ->assertOk()
        ->assertHeader('X-Request-Id', 'test-request-123')
        ->assertJsonPath('meta.request_id', 'test-request-123');
});

test('token ability enforcement blocks products when ability missing', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('products.read');

    $token = $user->createToken('limited', ['customers.read']);
    Sanctum::actingAs($user, ['customers.read'], 'sanctum');

    $this->withToken($token->plainTextToken)
        ->getJson('/api/v1/products')
        ->assertForbidden()
        ->assertJsonPath('success', false);
});

test('products api supports crud for authorized users', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['products.read', 'products.create', 'products.update', 'products.delete']);

    Sanctum::actingAs($user, ['*']);

    $created = $this->postJson('/api/v1/products', [
        'name' => 'API Widget',
        'sku' => 'API-001',
        'purchase_price' => 10,
        'sale_price' => 20,
        'stock_quantity' => 5,
        'minimum_stock_level' => 1,
    ])->assertCreated()->json('data');

    expect($created['sku'])->toBe('API-001');

    $this->getJson('/api/v1/products/'.$created['id'])
        ->assertOk()
        ->assertJsonPath('data.name', 'API Widget');

    $this->putJson('/api/v1/products/'.$created['id'], [
        'name' => 'Updated Widget',
        'sku' => 'API-001',
        'purchase_price' => 11,
        'sale_price' => 22,
        'stock_quantity' => 6,
        'minimum_stock_level' => 1,
    ])->assertOk()->assertJsonPath('data.name', 'Updated Widget');

    $this->deleteJson('/api/v1/products/'.$created['id'])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(Product::query()->whereKey($created['id'])->exists())->toBeFalse();
});

test('customers api scopes to customer contacts', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['contacts.read', 'contacts.create']);

    Sanctum::actingAs($user, ['*']);

    $customer = Contact::factory()->customer()->create(['name' => 'API Customer']);

    $this->getJson('/api/v1/customers/'.$customer->id)
        ->assertOk()
        ->assertJsonPath('data.name', 'API Customer');
});

test('api json error contract uses consistent structure', function () {
    $this->getJson('/api/v1/products')
        ->assertUnauthorized()
        ->assertJsonStructure(['success', 'message', 'errors', 'meta'])
        ->assertJsonPath('success', false);
});
