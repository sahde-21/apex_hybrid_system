<?php

use App\Models\Campaign;
use App\Enums\CampaignStatus;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Create Campaigns')] class extends \App\Livewire\ConcernBases\CampaignValidationRulesBase {
    public string $name = '';
    public string $code = '';
    public string $start_date = '';
    public string $end_date = '';
    public string $budget = '0';
    public string $status = 'draft';
    public string $description = '';

    public function mount(): void
    {
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
    }

    public function save(): void
    {
        $validated = $this->validate($this->campaignRules());

        Campaign::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Campaigns created successfully.'));

        $this->redirect(route('campaigns.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Campaigns') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:input wire:model="code" :label="__('Code')" required />
        <flux:input wire:model="start_date" type="date" :label="__('Start Date')" required />
        <flux:input wire:model="end_date" type="date" :label="__('End Date')" />
        <flux:input wire:model="budget" type="number" step="0.01" :label="__('Budget')" />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (CampaignStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:textarea wire:model="description" :label="__('Description')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('campaigns.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
