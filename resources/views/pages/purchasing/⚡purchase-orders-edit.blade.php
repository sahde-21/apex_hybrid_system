<?php

use App\Concerns\PurchaseOrderValidationRules;
use App\Enums\PurchaseOrderStatus;
use App\Models\Contact;
use App\Models\PurchaseOrder;
use App\Models\Warehouse;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit purchase order')] class extends Component {
    use PurchaseOrderValidationRules;

    public PurchaseOrder $purchaseOrder;

    public string $reference_number = '';
    public ?int $contact_id = null;
    public ?int $warehouse_id = null;
    public string $order_date = '';
    public string $expected_date = '';
    public string $status = 'draft';
    public string $total_amount = '0';
    public string $notes = '';

    public function mount(PurchaseOrder $purchaseOrder): void
    {
        $this->purchaseOrder = $purchaseOrder;
        $this->reference_number = $purchaseOrder->reference_number;
        $this->contact_id = $purchaseOrder->contact_id;
        $this->warehouse_id = $purchaseOrder->warehouse_id;
        $this->order_date = $purchaseOrder->order_date->format('Y-m-d');
        $this->expected_date = $purchaseOrder->expected_date?->format('Y-m-d') ?? '';
        $this->status = $purchaseOrder->status->value;
        $this->total_amount = (string) $purchaseOrder->total_amount;
        $this->notes = $purchaseOrder->notes ?? '';
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
        $validated = $this->validate($this->purchaseOrderRules($this->purchaseOrder->id));

        $this->purchaseOrder->update($validated);

        Flux::toast(variant: 'success', text: __('Purchase order updated successfully.'));

        $this->redirect(route('purchase-orders.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit purchase order') }}</flux:heading>
        <flux:subheading>{{ __('Update purchase order details') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="reference_number" :label="__('Reference number')" required />
        <flux:select wire:model="contact_id" :label="__('Supplier')" :placeholder="__('Select supplier')">
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
        <flux:input wire:model="expected_date" type="date" :label="__('Expected date')" />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (PurchaseOrderStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="total_amount" type="number" step="0.01" :label="__('Total amount')" required />
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('purchase-orders.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
