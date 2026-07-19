<?php

use App\Concerns\QuotationValidationRules;
use App\Enums\QuotationStatus;
use App\Models\Contact;
use App\Models\Quotation;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit quotation')] class extends Component {
    use QuotationValidationRules;

    public Quotation $quotation;

    public string $reference_number = '';
    public ?int $contact_id = null;
    public string $quotation_date = '';
    public string $valid_until = '';
    public string $status = 'draft';
    public string $total_amount = '0';
    public string $notes = '';

    public function mount(Quotation $quotation): void
    {
        $this->quotation = $quotation;
        $this->reference_number = $quotation->reference_number;
        $this->contact_id = $quotation->contact_id;
        $this->quotation_date = $quotation->quotation_date->format('Y-m-d');
        $this->valid_until = $quotation->valid_until?->format('Y-m-d') ?? '';
        $this->status = $quotation->status->value;
        $this->total_amount = (string) $quotation->total_amount;
        $this->notes = $quotation->notes ?? '';
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
        $validated = $this->validate($this->quotationRules($this->quotation->id));

        $this->quotation->update($validated);

        Flux::toast(variant: 'success', text: __('Quotation updated successfully.'));

        $this->redirect(route('quotations.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit quotation') }}</flux:heading>
        <flux:subheading>{{ __('Update quotation details') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="reference_number" :label="__('Reference number')" required />
        <flux:select wire:model="contact_id" :label="__('Contact')" :placeholder="__('Select contact')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->contacts as $contact)
                <flux:select.option :value="$contact->id">{{ $contact->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="quotation_date" type="date" :label="__('Quotation date')" required />
        <flux:input wire:model="valid_until" type="date" :label="__('Valid until')" />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (QuotationStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="total_amount" type="number" step="0.01" :label="__('Total amount')" required />
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('quotations.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
