<?php

use App\Models\Campaign;
use App\Enums\CampaignStatus;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Edit Campaigns')] class extends \App\Livewire\ConcernBases\CampaignValidationRulesBase {
    public Campaign $campaign;

    public string $name = '';
    public string $code = '';
    public string $start_date = '';
    public string $end_date = '';
    public string $budget = '0';
    public string $status = 'draft';
    public string $description = '';

    public function mount(Campaign $campaign): void
    {
        $this->campaign = $campaign;
        $this->name = $campaign->name ?? '';
        $this->code = $campaign->code ?? '';
        $this->start_date = $campaign->start_date?->format('Y-m-d') ?? '';
        $this->end_date = $campaign->end_date?->format('Y-m-d') ?? '';
        $this->budget = (string) $campaign->budget;
        $this->status = $campaign->status->value;
        $this->description = $campaign->description ?? '';
    }

    public function save(): void
    {
        $validated = $this->validate($this->campaignUpdateRules($this->campaign->id));

        $this->campaign->update($validated);

        Flux::toast(variant: 'success', text: __('Campaigns updated successfully.'));

        $this->redirect(route('campaigns.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit Campaigns') }}</flux:heading>
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
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('campaigns.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
