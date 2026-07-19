<?php

use App\Enums\ContactType;
use App\Models\Contact;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('super-admin');

    $this->actingAs($user);
});

test('contacts index page is displayed', function () {
    $this->get(route('contacts.index'))->assertOk();
});

test('contact can be created via livewire', function () {
    Livewire::test('pages::contacts.contacts-create')
        ->set('name', 'Jane Doe')
        ->set('type', ContactType::Customer->value)
        ->set('company_name', 'Acme Corp')
        ->set('email', 'jane@example.com')
        ->set('phone', '+1 555-0100')
        ->set('address', '123 Main St')
        ->set('tax_number', 'TAX-123456')
        ->set('opening_balance', '1500.00')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('contacts.index'));

    expect(Contact::query()->where('email', 'jane@example.com')->exists())->toBeTrue();
});

test('contact can be updated via livewire', function () {
    $contact = Contact::factory()->create(['name' => 'Original Name']);

    Livewire::test('pages::contacts.contacts-edit', ['contact' => $contact])
        ->set('name', 'Updated Name')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('contacts.index'));

    expect($contact->fresh()->name)->toBe('Updated Name');
});

test('contact can be deleted via livewire', function () {
    $contact = Contact::factory()->create();

    Livewire::test('pages::contacts.contacts-index')
        ->call('confirmDelete', $contact->id)
        ->call('deleteContact')
        ->assertHasNoErrors();

    expect(Contact::query()->find($contact->id))->toBeNull();
});

test('contact can be stored via controller', function () {
    $this->post(route('contacts.store'), [
        'name' => 'Controller Contact',
        'type' => ContactType::Supplier->value,
        'company_name' => 'Supply Co',
        'email' => 'supplier@example.com',
        'phone' => '+1 555-0200',
        'address' => '456 Oak Ave',
        'tax_number' => 'TAX-789012',
        'opening_balance' => -750.50,
    ])->assertRedirect(route('contacts.index'));

    expect(Contact::query()->where('email', 'supplier@example.com')->exists())->toBeTrue();
});
