<?php

use App\Models\LoyaltyProgram;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Create Loyalty programs')] class extends \App\Livewire\ConcernBases\LoyaltyProgramValidationRulesBase {
    public string $name = '';
    public string $code = '';
    public string $points_per_currency = '1';
    public bool $is_active = true;
    public string $description = '';

    public function mount(): void
    {
    }

    public function save(): void
    {
        $validated = $this->validate($this->loyaltyProgramRules());

        LoyaltyProgram::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Loyalty programs created successfully.'));

        $this->redirect(route('loyalty-programs.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Loyalty programs') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:input wire:model="code" :label="__('Code')" required />
        <flux:input wire:model="points_per_currency" type="number" step="0.01" :label="__('Points Per Currency')" />
        <flux:switch wire:model="is_active" :label="__('Is Active')" />
        <flux:textarea wire:model="description" :label="__('Description')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('loyalty-programs.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
