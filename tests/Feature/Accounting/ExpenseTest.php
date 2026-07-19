<?php

use App\Models\Expense;
use Livewire\Livewire;

beforeEach(fn () => actingAsSuperAdmin());

test('expenses index is displayed', function () {
    Expense::factory()->count(2)->create();

    $this->get(route('expenses.index'))->assertOk();
});

test('expense can be stored via controller', function () {
    $this->post(route('expenses.store'), [
        'reference_number' => 'EXP-TEST-001',
        'category' => 'Travel',
        'description' => 'Taxi fare',
        'amount' => 42.75,
        'expense_date' => now()->toDateString(),
        'payment_method' => 'cash',
    ])->assertRedirect(route('expenses.index'));

    expect(Expense::query()->where('reference_number', 'EXP-TEST-001')->exists())->toBeTrue();
});

test('expense can be updated and deleted', function () {
    $expense = Expense::factory()->create(['amount' => 10]);

    $this->put(route('expenses.update', $expense), [
        'reference_number' => $expense->reference_number,
        'category' => 'Office',
        'description' => 'Updated expense',
        'amount' => 99.99,
        'expense_date' => $expense->expense_date->toDateString(),
        'payment_method' => 'card',
    ])->assertRedirect(route('expenses.index'));

    expect((float) $expense->fresh()->amount)->toBe(99.99);

    Livewire::test('pages::accounting.expenses-index')
        ->call('confirmDelete', $expense->id)
        ->call('deleteExpense')
        ->assertHasNoErrors();

    expect(Expense::query()->find($expense->id))->toBeNull();
});

test('accountant can access expenses but not users', function () {
    actingAsRole('accountant');

    $this->get(route('expenses.index'))->assertOk();
    $this->get(route('payments.index'))->assertOk();
    $this->get(route('users.index'))->assertForbidden();
});
