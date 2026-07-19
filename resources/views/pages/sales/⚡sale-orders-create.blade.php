<?php

use App\Concerns\SaleOrderValidationRules;
use App\Enums\SaleOrderStatus;
use App\Models\Contact;
use App\Models\SaleOrder;
use App\Models\Warehouse;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create sale order')] class extends Component {
    use SaleOrderValidationRules;

    public string $reference_number = '';
    public ?int $contact_id = null;
    public ?int $warehouse_id = null;
    public string $order_date = '';
    public string $delivery_date = '';
    public string $status = 'draft';
    public string $total_amount = '0';
    public string $notes = '';

    public function mount(): void
    {
        $this->order_date = now()->format('Y-m-d');
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
     * @return \Illuminate\Database\Eloquent\Collection<int, Warehouse>
     */
    #[Computed]
    public function warehouses()
    {
        return Warehouse::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->saleOrderRules());

        SaleOrder::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Sale order created successfully.'));

        $this->redirect(route('sale-orders.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create sale order') }}</flux:heading>
        <flux:subheading>{{ __('Record a new customer sale order') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="reference_number" :label="__('Reference number')" required />
        <flux:select wire:model="contact_id" :label="__('Customer')" :placeholder="__('Select customer')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->contacts as $contact)
                <flux:select.option :value="$contact->id">{{ $contact->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model="warehouse_id" :label="__('Warehouse')" :placeholder="__('Select warehouse')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->warehouses as $warehouse)
                <flux:select.option :value="$warehouse->id">{{ $warehouse->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="order_date" type="date" :label="__('Order date')" required />
        <flux:input wire:model="delivery_date" type="date" :label="__('Delivery date')" />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (SaleOrderStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="total_amount" type="number" step="0.01" :label="__('Total amount')" required />
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create sale order') }}</flux:button>
            <flux:button :href="route('sale-orders.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
