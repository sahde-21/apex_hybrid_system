<?php

use App\Models\Contact;
use App\Models\Quotation;
use App\Models\User;
use App\Enums\QuotationStatus;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('quotation workflow actions work through the api', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([
        'quotations.read', 'quotations.create', 'quotations.update',
        'quotations.send', 'quotations.approve', 'quotations.convert',
        'sale-orders.read', 'sale-orders.create',
    ]);

    Sanctum::actingAs($user, ['sales.write', 'sales.read']);

    $customer = Contact::factory()->customer()->create();

    $response = $this->postJson('/api/v1/quotations', [
        'reference_number' => 'QT-API-001',
        'contact_id' => $customer->id,
        'quotation_date' => now()->toDateString(),
        'lines' => [
            [
                'description' => 'Consulting',
                'quantity' => 1,
                'unit_price' => 100,
            ],
        ],
    ])->assertCreated();

    $quotationId = $response->json('data.id');

    $this->postJson("/api/v1/quotations/{$quotationId}/send")
        ->assertOk()
        ->assertJsonPath('data.status.value', QuotationStatus::Sent->value);

    $this->postJson("/api/v1/quotations/{$quotationId}/accept")
        ->assertOk()
        ->assertJsonPath('data.status.value', QuotationStatus::Accepted->value);

    $this->postJson("/api/v1/quotations/{$quotationId}/convert-to-sale-order", [], [
        'Idempotency-Key' => 'convert-qt-api-001',
    ])->assertCreated()
        ->assertJsonPath('success', true);

    expect(Quotation::find($quotationId)?->status)->toBe(QuotationStatus::Converted);
});

test('duplicate idempotency key returns original response', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['quotations.read', 'quotations.create']);

    Sanctum::actingAs($user, ['*']);

    $payload = [
        'reference_number' => 'QT-IDEM-001',
        'quotation_date' => now()->toDateString(),
        'lines' => [
            ['description' => 'Line', 'quantity' => 1, 'unit_price' => 50],
        ],
    ];

    $first = $this->postJson('/api/v1/quotations', $payload, [
        'Idempotency-Key' => 'idem-qt-001',
    ])->assertCreated();

    $second = $this->postJson('/api/v1/quotations', $payload, [
        'Idempotency-Key' => 'idem-qt-001',
    ])->assertCreated();

    expect($second->json('data.id'))->toBe($first->json('data.id'));
});

test('idempotency conflict returns 409 for mismatched payload', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['quotations.read', 'quotations.create']);

    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/v1/quotations', [
        'reference_number' => 'QT-IDEM-002',
        'quotation_date' => now()->toDateString(),
        'lines' => [['description' => 'A', 'quantity' => 1, 'unit_price' => 10]],
    ], ['Idempotency-Key' => 'idem-conflict-001'])->assertCreated();

    $this->postJson('/api/v1/quotations', [
        'reference_number' => 'QT-IDEM-003',
        'quotation_date' => now()->toDateString(),
        'lines' => [['description' => 'B', 'quantity' => 1, 'unit_price' => 10]],
    ], ['Idempotency-Key' => 'idem-conflict-001'])
        ->assertStatus(409)
        ->assertJsonPath('success', false);
});
