<?php

use App\Models\Contract;
use App\Enums\ContractStatus;
use App\Models\Contact;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Create Contracts')] class extends \App\Livewire\ConcernBases\ContractValidationRulesBase {
    public string $reference_number = '';
    public ?int $contact_id = null;
    public string $title = '';
    public string $start_date = '';
    public string $end_date = '';
    public string $value = '0';
    public string $status = 'draft';
    public string $notes = '';

    public function mount(): void
    {
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
    }

    #[Computed]
    public function contacts()
    {
        return \App\Models\Contact::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->contractRules());

        Contract::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Contracts created successfully.'));

        $this->redirect(route('contracts.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Contracts') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="reference_number" :label="__('Reference Number')" required />
        <flux:select wire:model="contact_id" :label="__('Contact Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->contacts as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="title" :label="__('Title')" required />
        <flux:input wire:model="start_date" type="date" :label="__('Start Date')" required />
        <flux:input wire:model="end_date" type="date" :label="__('End Date')" />
        <flux:input wire:model="value" type="number" step="0.01" :label="__('Value')" />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (ContractStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('contracts.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
