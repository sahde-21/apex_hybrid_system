<?php

use App\Concerns\ExpenseValidationRules;
use App\Models\Contact;
use App\Models\Expense;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create expense')] class extends Component {
    use ExpenseValidationRules;

    public string $reference_number = '';
    public ?int $contact_id = null;
    public string $category = '';
    public string $description = '';
    public string $amount = '0';
    public string $expense_date = '';
    public string $payment_method = '';
    public string $notes = '';

    public function mount(): void
    {
        $this->expense_date = now()->format('Y-m-d');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Contact>
     */
    #[Computed]
    public function contacts()
    {
        return Contact::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->expenseRules());

        Expense::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Expense created successfully.'));

        $this->redirect(route('expenses.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create expense') }}</flux:heading>
        <flux:subheading>{{ __('Record a new business expense') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="reference_number" :label="__('Reference number')" required />
        <flux:select wire:model="contact_id" :label="__('Contact')" :placeholder="__('Select contact')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->contacts as $contact)
                <flux:select.option :value="$contact->id">{{ $contact->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="category" :label="__('Category')" required />
        <flux:input wire:model="description" :label="__('Description')" required />
        <flux:input wire:model="amount" type="number" step="0.01" :label="__('Amount')" required />
        <flux:input wire:model="expense_date" type="date" :label="__('Expense date')" required />
        <flux:input wire:model="payment_method" :label="__('Payment method')" />
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create expense') }}</flux:button>
            <flux:button :href="route('expenses.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
