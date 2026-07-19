<?php

use App\Concerns\TicketValidationRules;
use App\Models\Ticket;
use App\Enums\TicketStatus;
use App\Models\Contact;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Tickets')] class extends Component {
    use TicketValidationRules;
    public Ticket $ticket;

    public string $reference_number = '';
    public ?int $contact_id = null;
    public string $subject = '';
    public string $priority = 'medium';
    public string $status = 'open';
    public string $description = '';

    public function mount(Ticket $ticket): void
    {
        $this->ticket = $ticket;
        $this->reference_number = $ticket->reference_number ?? '';
        $this->contact_id = $ticket->contact_id;
        $this->subject = $ticket->subject ?? '';
        $this->priority = $ticket->priority ?? '';
        $this->status = $ticket->status->value;
        $this->description = $ticket->description ?? '';
    }

    #[Computed]
    public function contacts()
    {
        return \App\Models\Contact::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->ticketUpdateRules($this->ticket->id));

        $this->ticket->update($validated);

        Flux::toast(variant: 'success', text: __('Tickets updated successfully.'));

        $this->redirect(route('tickets.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit Tickets') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="reference_number" :label="__('Reference Number')" required />
        <flux:select wire:model="contact_id" :label="__('Contact Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->contacts as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="subject" :label="__('Subject')" required />
        <flux:input wire:model="priority" :label="__('Priority')" />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (TicketStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:textarea wire:model="description" :label="__('Description')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('tickets.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
