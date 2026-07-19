<?php

use App\Concerns\LeadValidationRules;
use App\Models\Lead;
use App\Enums\LeadStatus;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Lead management')] class extends Component {
    use LeadValidationRules;
    public Lead $lead;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $company = '';
    public string $source = '';
    public string $status = 'new';
    public string $estimated_value = '0';
    public string $notes = '';

    public function mount(Lead $lead): void
    {
        $this->lead = $lead;
        $this->name = $lead->name ?? '';
        $this->email = $lead->email ?? '';
        $this->phone = $lead->phone ?? '';
        $this->company = $lead->company ?? '';
        $this->source = $lead->source ?? '';
        $this->status = $lead->status->value;
        $this->estimated_value = (string) $lead->estimated_value;
        $this->notes = $lead->notes ?? '';
    }

    public function save(): void
    {
        $validated = $this->validate($this->leadUpdateRules($this->lead->id));

        $this->lead->update($validated);

        Flux::toast(variant: 'success', text: __('Lead management updated successfully.'));

        $this->redirect(route('leads.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit Lead management') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="name" :label="__('Name')" required />
        <flux:input wire:model="email" :label="__('Email')" />
        <flux:input wire:model="phone" :label="__('Phone')" />
        <flux:input wire:model="company" :label="__('Company')" />
        <flux:input wire:model="source" :label="__('Source')" />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (LeadStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="estimated_value" type="number" step="0.01" :label="__('Estimated Value')" />
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('leads.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
