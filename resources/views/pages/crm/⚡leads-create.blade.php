<?php

use App\Models\Lead;
use App\Enums\LeadStatus;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Create Lead management')] class extends \App\Livewire\ConcernBases\LeadValidationRulesBase {
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $company = '';
    public string $source = '';
    public string $status = 'new';
    public string $estimated_value = '0';
    public string $notes = '';

    public function mount(): void
    {
    }

    public function save(): void
    {
        $validated = $this->validate($this->leadRules());

        Lead::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Lead management created successfully.'));

        $this->redirect(route('leads.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Lead management') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:input wire:model="email" :label="__('Email')" />
        <flux:input wire:model="phone" :label="__('Phone')" />
        <flux:input wire:model="company" :label="__('Company')" />
        <flux:input wire:model="source" :label="__('Source')" />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (LeadStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="estimated_value" type="number" step="0.01" :label="__('Estimated Value')" />
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('leads.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
