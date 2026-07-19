<?php

use App\Concerns\CrmInteractionValidationRules;
use App\Enums\CrmInteractionType;
use App\Models\Contact;
use App\Models\CrmInteraction;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create CRM interaction')] class extends Component {
    use CrmInteractionValidationRules;

    public ?int $contact_id = null;
    public string $interaction_type = 'call';
    public string $subject = '';
    public string $description = '';
    public string $interaction_date = '';
    public string $follow_up_date = '';

    public function mount(): void
    {
        $this->interaction_date = now()->format('Y-m-d');
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
        $validated = $this->validate($this->crmInteractionRules());

        CrmInteraction::query()->create($validated);

        Flux::toast(variant: 'success', text: __('CRM interaction created successfully.'));

        $this->redirect(route('crm-interactions.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create CRM interaction') }}</flux:heading>
        <flux:subheading>{{ __('Log a new customer interaction') }}</flux:subheading>
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
            <flux:button type="submit" variant="primary">{{ __('Create CRM interaction') }}</flux:button>
            <flux:button :href="route('crm-interactions.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
