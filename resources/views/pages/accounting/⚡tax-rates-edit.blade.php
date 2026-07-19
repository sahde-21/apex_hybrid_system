<?php

use App\Concerns\TaxRateValidationRules;
use App\Models\TaxRate;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit tax rate')] class extends Component {
    use TaxRateValidationRules;

    public TaxRate $taxRate;

    public string $name = '';
    public string $code = '';
    public string $rate = '0';
    public bool $is_active = true;
    public string $description = '';

    public function mount(TaxRate $taxRate): void
    {
        $this->taxRate = $taxRate;
        $this->name = $taxRate->name;
        $this->code = $taxRate->code;
        $this->rate = (string) $taxRate->rate;
        $this->is_active = $taxRate->is_active;
        $this->description = $taxRate->description ?? '';
    }

    public function save(): void
    {
        $validated = $this->validate($this->taxRateRules($this->taxRate->id));

        $this->taxRate->update($validated);

        Flux::toast(variant: 'success', text: __('Tax rate updated successfully.'));

        $this->redirect(route('tax-rates.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit tax rate') }}</flux:heading>
        <flux:subheading>{{ __('Update tax rate details') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:input wire:model="code" :label="__('Code')" required />
        <flux:input wire:model="rate" type="number" step="0.01" min="0" max="100" :label="__('Rate')" required />
        <flux:switch wire:model="is_active" :label="__('Active')" />
        <flux:textarea wire:model="description" :label="__('Description')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('tax-rates.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
