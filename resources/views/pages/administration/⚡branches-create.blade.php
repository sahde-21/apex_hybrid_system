<?php

use App\Concerns\BranchValidationRules;
use App\Models\Branch;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Branches')] class extends Component {
    use BranchValidationRules;
    public string $name = '';
    public string $code = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';
    public bool $is_active = true;

    public function mount(): void
    {
    }

    public function save(): void
    {
        $validated = $this->validate($this->branchRules());

        Branch::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Branches created successfully.'));

        $this->redirect(route('branches.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Branches') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:input wire:model="code" :label="__('Code')" required />
        <flux:textarea wire:model="address" :label="__('Address')" />
        <flux:input wire:model="phone" :label="__('Phone')" />
        <flux:input wire:model="email" :label="__('Email')" />
        <flux:switch wire:model="is_active" :label="__('Is Active')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('branches.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
