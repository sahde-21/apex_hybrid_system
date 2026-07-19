<?php

use App\Concerns\InvoiceValidationRules;
use App\Enums\InvoiceStatus;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\SaleOrder;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create invoice')] class extends Component {
    use InvoiceValidationRules;

    public string $reference_number = '';
    public ?int $contact_id = null;
    public ?int $sale_order_id = null;
    public string $invoice_date = '';
    public string $due_date = '';
    public string $status = 'draft';
    public string $total_amount = '0';
    public string $tax_amount = '0';
    public string $notes = '';

    public function mount(): void
    {
        $this->invoice_date = now()->format('Y-m-d');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Contact>
     */
    #[Computed]
    public function contacts()
    {
        return Contact::query()->orderBy('name')->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, SaleOrder>
     */
    #[Computed]
    public function saleOrders()
    {
        return SaleOrder::query()->orderBy('reference_number')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->invoiceRules());

        Invoice::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Invoice created successfully.'));

        $this->redirect(route('invoices.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create invoice') }}</flux:heading>
        <flux:subheading>{{ __('Record a new sales invoice') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="reference_number" :label="__('Reference number')" required />
        <flux:select wire:model="contact_id" :label="__('Contact')" :placeholder="__('Select contact')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->contacts as $contact)
                <flux:select.option :value="$contact->id">{{ $contact->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model="sale_order_id" :label="__('Sale order')" :placeholder="__('Select sale order')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->saleOrders as $saleOrder)
                <flux:select.option :value="$saleOrder->id">{{ $saleOrder->reference_number }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="invoice_date" type="date" :label="__('Invoice date')" required />
        <flux:input wire:model="due_date" type="date" :label="__('Due date')" />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (InvoiceStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="total_amount" type="number" step="0.01" :label="__('Total amount')" required />
        <flux:input wire:model="tax_amount" type="number" step="0.01" :label="__('Tax amount')" required />
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create invoice') }}</flux:button>
            <flux:button :href="route('invoices.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
