<?php

use App\Concerns\TaxRateValidationRules;
use App\Models\TaxRate;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create tax rate')] class extends Component {
    use TaxRateValidationRules;

    public string $name = '';
    public string $code = '';
    public string $rate = '0';
    public bool $is_active = true;
    public string $description = '';

    public function save(): void
    {
        $validated = $this->validate($this->taxRateRules());

        TaxRate::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Tax rate created successfully.'));

        $this->redirect(route('tax-rates.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create tax rate') }}</flux:heading>
        <flux:subheading>{{ __('Add a new tax rate') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:input wire:model="code" :label="__('Code')" required />
        <flux:input wire:model="rate" type="number" step="0.01" min="0" max="100" :label="__('Rate')" required />
        <flux:switch wire:model="is_active" :label="__('Active')" />
        <flux:textarea wire:model="description" :label="__('Description')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create tax rate') }}</flux:button>
            <flux:button :href="route('tax-rates.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
