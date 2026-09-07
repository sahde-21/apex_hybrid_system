<?php

use App\Models\FixedAsset;
use App\Models\Branch;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Create Fixed assets')] class extends \App\Livewire\ConcernBases\FixedAssetValidationRulesBase {
    public string $name = '';
    public string $asset_code = '';
    public string $purchase_date = '';
    public string $purchase_cost = '0';
    public string $current_value = '0';
    public ?int $branch_id = null;
    public string $notes = '';

    public function mount(): void
    {
        $this->purchase_date = now()->format('Y-m-d');
    }

    #[Computed]
    public function branches()
    {
        return \App\Models\Branch::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->fixedAssetRules());

        FixedAsset::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Fixed assets created successfully.'));

        $this->redirect(route('fixed-assets.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Fixed assets') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:input wire:model="asset_code" :label="__('Asset Code')" required />
        <flux:input wire:model="purchase_date" type="date" :label="__('Purchase Date')" required />
        <flux:input wire:model="purchase_cost" type="number" step="0.01" :label="__('Purchase Cost')" required />
        <flux:input wire:model="current_value" type="number" step="0.01" :label="__('Current Value')" />
        <flux:select wire:model="branch_id" :label="__('Branch Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->branches as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('fixed-assets.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
