<?php

use App\Concerns\BillValidationRules;
use App\Enums\BillStatus;
use App\Models\Bill;
use App\Models\Contact;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit bill')] class extends Component {
    use BillValidationRules;

    public Bill $bill;

    public string $reference_number = '';
    public ?int $contact_id = null;
    public string $bill_date = '';
    public string $due_date = '';
    public string $status = 'draft';
    public string $total_amount = '0';
    public string $tax_amount = '0';
    public string $notes = '';

    public function mount(Bill $bill): void
    {
        $this->bill = $bill;
        $this->reference_number = $bill->reference_number;
        $this->contact_id = $bill->contact_id;
        $this->bill_date = $bill->bill_date->format('Y-m-d');
        $this->due_date = $bill->due_date?->format('Y-m-d') ?? '';
        $this->status = $bill->status->value;
        $this->total_amount = (string) $bill->total_amount;
        $this->tax_amount = (string) $bill->tax_amount;
        $this->notes = $bill->notes ?? '';
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
        $validated = $this->validate($this->billRules($this->bill->id));

        $this->bill->update($validated);

        Flux::toast(variant: 'success', text: __('Bill updated successfully.'));

        $this->redirect(route('bills.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit bill') }}</flux:heading>
        <flux:subheading>{{ __('Update bill details') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="reference_number" :label="__('Reference number')" required />
        <flux:select wire:model="contact_id" :label="__('Contact')" :placeholder="__('Select contact')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->contacts as $contact)
                <flux:select.option :value="$contact->id">{{ $contact->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="bill_date" type="date" :label="__('Bill date')" required />
        <flux:input wire:model="due_date" type="date" :label="__('Due date')" />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (BillStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="total_amount" type="number" step="0.01" :label="__('Total amount')" required />
        <flux:input wire:model="tax_amount" type="number" step="0.01" :label="__('Tax amount')" required />
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('bills.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
