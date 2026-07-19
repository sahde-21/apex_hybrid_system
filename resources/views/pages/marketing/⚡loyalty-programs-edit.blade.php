<?php

use App\Concerns\LoyaltyProgramValidationRules;
use App\Models\LoyaltyProgram;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Loyalty programs')] class extends Component {
    use LoyaltyProgramValidationRules;
    public LoyaltyProgram $loyaltyProgram;

    public string $name = '';
    public string $code = '';
    public string $points_per_currency = '1';
    public bool $is_active = true;
    public string $description = '';

    public function mount(LoyaltyProgram $loyaltyProgram): void
    {
        $this->loyaltyProgram = $loyaltyProgram;
        $this->name = $loyaltyProgram->name ?? '';
        $this->code = $loyaltyProgram->code ?? '';
        $this->points_per_currency = (string) $loyaltyProgram->points_per_currency;
        $this->is_active = $loyaltyProgram->is_active;
        $this->description = $loyaltyProgram->description ?? '';
    }

    public function save(): void
    {
        $validated = $this->validate($this->loyaltyProgramUpdateRules($this->loyaltyProgram->id));

        $this->loyaltyProgram->update($validated);

        Flux::toast(variant: 'success', text: __('Loyalty programs updated successfully.'));

        $this->redirect(route('loyalty-programs.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit Loyalty programs') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:input wire:model="code" :label="__('Code')" required />
        <flux:input wire:model="points_per_currency" type="number" step="0.01" :label="__('Points Per Currency')" />
        <flux:switch wire:model="is_active" :label="__('Is Active')" />
        <flux:textarea wire:model="description" :label="__('Description')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('loyalty-programs.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
