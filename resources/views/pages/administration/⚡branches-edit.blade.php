<?php

use App\Concerns\BranchValidationRules;
use App\Models\Branch;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Branches')] class extends Component {
    use BranchValidationRules;
    public Branch $branch;

    public string $name = '';
    public string $code = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';
    public bool $is_active = true;

    public function mount(Branch $branch): void
    {
        $this->branch = $branch;
        $this->name = $branch->name ?? '';
        $this->code = $branch->code ?? '';
        $this->address = $branch->address ?? '';
        $this->phone = $branch->phone ?? '';
        $this->email = $branch->email ?? '';
        $this->is_active = $branch->is_active;
    }

    public function save(): void
    {
        $validated = $this->validate($this->branchUpdateRules($this->branch->id));

        $this->branch->update($validated);

        Flux::toast(variant: 'success', text: __('Branches updated successfully.'));

        $this->redirect(route('branches.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit Branches') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:input wire:model="code" :label="__('Code')" required />
        <flux:textarea wire:model="address" :label="__('Address')" />
        <flux:input wire:model="phone" :label="__('Phone')" />
        <flux:input wire:model="email" :label="__('Email')" />
        <flux:switch wire:model="is_active" :label="__('Is Active')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('branches.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
