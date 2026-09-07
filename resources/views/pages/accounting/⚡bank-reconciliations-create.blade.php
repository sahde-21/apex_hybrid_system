<?php

use App\Models\BankReconciliation;
use App\Enums\BankReconciliationStatus;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Create Bank reconciliation')] class extends \App\Livewire\ConcernBases\BankReconciliationValidationRulesBase {
    public string $reference_number = '';
    public string $bank_name = '';
    public string $statement_date = '';
    public string $opening_balance = '0';
    public string $closing_balance = '0';
    public string $status = 'draft';
    public string $notes = '';

    public function mount(): void
    {
        $this->statement_date = now()->format('Y-m-d');
    }

    public function save(): void
    {
        $validated = $this->validate($this->bankReconciliationRules());

        BankReconciliation::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Bank reconciliation created successfully.'));

        $this->redirect(route('bank-reconciliations.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Bank reconciliation') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="reference_number" :label="__('Reference Number')" required />
        <flux:input wire:model="bank_name" :label="__('Bank Name')" required />
        <flux:input wire:model="statement_date" type="date" :label="__('Statement Date')" required />
        <flux:input wire:model="opening_balance" type="number" step="0.01" :label="__('Opening Balance')" />
        <flux:input wire:model="closing_balance" type="number" step="0.01" :label="__('Closing Balance')" />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (BankReconciliationStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('bank-reconciliations.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
