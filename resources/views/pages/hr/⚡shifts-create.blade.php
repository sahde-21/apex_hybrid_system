<?php

use App\Concerns\ShiftValidationRules;
use App\Models\Shift;
use App\Models\Branch;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Shift management')] class extends Component {
    use ShiftValidationRules;
    public string $name = '';
    public ?int $branch_id = null;
    public string $start_time = '';
    public string $end_time = '';
    public bool $is_active = true;

    public function mount(): void
    {
    }

    #[Computed]
    public function branches()
    {
        return \App\Models\Branch::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->shiftRules());

        Shift::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Shift management created successfully.'));

        $this->redirect(route('shifts.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Shift management') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:select wire:model="branch_id" :label="__('Branch Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->branches as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="start_time" type="time" :label="__('Start Time')" />
        <flux:input wire:model="end_time" type="time" :label="__('End Time')" />
        <flux:switch wire:model="is_active" :label="__('Is Active')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('shifts.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
