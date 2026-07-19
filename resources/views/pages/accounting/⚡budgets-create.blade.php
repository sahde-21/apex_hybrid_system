<?php

use App\Concerns\BudgetValidationRules;
use App\Models\Budget;
use App\Models\Branch;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Budgeting')] class extends Component {
    use BudgetValidationRules;
    public string $name = '';
    public string $reference_number = '';
    public string $period_start = '';
    public string $period_end = '';
    public string $allocated_amount = '0';
    public string $spent_amount = '0';
    public ?int $branch_id = null;
    public string $notes = '';

    public function mount(): void
    {
    }

    #[Computed]
    public function branches()
    {
        return \App\Models\Branch::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->budgetRules());

        Budget::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Budgeting created successfully.'));

        $this->redirect(route('budgets.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Budgeting') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:input wire:model="reference_number" :label="__('Reference Number')" required />
        <flux:input wire:model="period_start" type="date" :label="__('Period Start')" required />
        <flux:input wire:model="period_end" type="date" :label="__('Period End')" required />
        <flux:input wire:model="allocated_amount" type="number" step="0.01" :label="__('Allocated Amount')" required />
        <flux:input wire:model="spent_amount" type="number" step="0.01" :label="__('Spent Amount')" />
        <flux:select wire:model="branch_id" :label="__('Branch Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->branches as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('budgets.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
