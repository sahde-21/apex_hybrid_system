<?php

use App\Concerns\SubscriptionValidationRules;
use App\Models\Subscription;
use App\Enums\SubscriptionStatus;
use App\Models\Contact;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Subscriptions')] class extends Component {
    use SubscriptionValidationRules;
    public ?int $contact_id = null;
    public string $plan_name = '';
    public string $start_date = '';
    public string $end_date = '';
    public string $amount = '0';
    public string $billing_cycle = 'monthly';
    public string $status = 'active';

    public function mount(): void
    {
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
    }

    #[Computed]
    public function contacts()
    {
        return \App\Models\Contact::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->subscriptionRules());

        Subscription::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Subscriptions created successfully.'));

        $this->redirect(route('subscriptions.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Subscriptions') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:select wire:model="contact_id" :label="__('Contact Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->contacts as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="plan_name" :label="__('Plan Name')" required />
        <flux:input wire:model="start_date" type="date" :label="__('Start Date')" required />
        <flux:input wire:model="end_date" type="date" :label="__('End Date')" />
        <flux:input wire:model="amount" type="number" step="0.01" :label="__('Amount')" required />
        <flux:input wire:model="billing_cycle" :label="__('Billing Cycle')" />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (SubscriptionStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('subscriptions.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
