<?php

use App\Enums\CrmInteractionType;
use App\Models\Contact;
use App\Models\CrmInteraction;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Edit CRM interaction')] class extends \App\Livewire\ConcernBases\CrmInteractionValidationRulesBase {

    public CrmInteraction $crmInteraction;

    public ?int $contact_id = null;
    public string $interaction_type = 'call';
    public string $subject = '';
    public string $description = '';
    public string $interaction_date = '';
    public string $follow_up_date = '';

    public function mount(CrmInteraction $crmInteraction): void
    {
        $this->crmInteraction = $crmInteraction;
        $this->contact_id = $crmInteraction->contact_id;
        $this->interaction_type = $crmInteraction->interaction_type->value;
        $this->subject = $crmInteraction->subject;
        $this->description = $crmInteraction->description ?? '';
        $this->interaction_date = $crmInteraction->interaction_date->format('Y-m-d');
        $this->follow_up_date = $crmInteraction->follow_up_date?->format('Y-m-d') ?? '';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Contact>
     */
    #[Computed]
    public function contacts()
    {
        return Contact::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->crmInteractionRules($this->crmInteraction->id));

        $this->crmInteraction->update($validated);

        Flux::toast(variant: 'success', text: __('CRM interaction updated successfully.'));

        $this->redirect(route('crm-interactions.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit CRM interaction') }}</flux:heading>
        <flux:subheading>{{ __('Update CRM interaction details') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:select wire:model="contact_id" :label="__('Contact')" :placeholder="__('Select contact')" required>
            @foreach ($this->contacts as $contact)
                <flux:select.option :value="$contact->id">{{ $contact->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model="interaction_type" :label="__('Interaction type')">
            @foreach (CrmInteractionType::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="subject" :label="__('Subject')" required />
        <flux:textarea wire:model="description" :label="__('Description')" />
        <flux:input wire:model="interaction_date" type="date" :label="__('Interaction date')" required />
        <flux:input wire:model="follow_up_date" type="date" :label="__('Follow-up date')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('crm-interactions.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
