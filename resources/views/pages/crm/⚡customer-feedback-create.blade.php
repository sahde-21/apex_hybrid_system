<?php

use App\Models\CustomerFeedback;
use App\Models\Contact;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Create Customer feedback')] class extends \App\Livewire\ConcernBases\CustomerFeedbackValidationRulesBase {
    public ?int $contact_id = null;
    public int $rating = 0;
    public string $subject = '';
    public string $feedback = '';
    public string $feedback_date = '';

    public function mount(): void
    {
        $this->feedback_date = now()->format('Y-m-d');
    }

    #[Computed]
    public function contacts()
    {
        return \App\Models\Contact::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->customerFeedbackRules());

        CustomerFeedback::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Customer feedback created successfully.'));

        $this->redirect(route('customer-feedback.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Customer feedback') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:select wire:model="contact_id" :label="__('Contact Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->contacts as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="rating" type="number" :label="__('Rating')" required />
        <flux:input wire:model="subject" :label="__('Subject')" required />
        <flux:textarea wire:model="feedback" :label="__('Feedback')" />
        <flux:input wire:model="feedback_date" type="date" :label="__('Feedback Date')" required />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('customer-feedback.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
