<?php

use App\Concerns\StockTransferValidationRules;
use App\Models\StockTransfer;
use App\Enums\StockTransferStatus;
use App\Models\Product;
use App\Models\Warehouse;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Stock transfers')] class extends Component {
    use StockTransferValidationRules;
    public StockTransfer $stockTransfer;

    public string $reference_number = '';
    public ?int $product_id = null;
    public ?int $from_warehouse_id = null;
    public ?int $to_warehouse_id = null;
    public int $quantity = 0;
    public string $transfer_date = '';
    public string $status = 'draft';
    public string $notes = '';

    public function mount(StockTransfer $stockTransfer): void
    {
        $this->stockTransfer = $stockTransfer;
        $this->reference_number = $stockTransfer->reference_number ?? '';
        $this->product_id = $stockTransfer->product_id;
        $this->from_warehouse_id = $stockTransfer->from_warehouse_id;
        $this->to_warehouse_id = $stockTransfer->to_warehouse_id;
        $this->quantity = (string) $stockTransfer->quantity;
        $this->transfer_date = $stockTransfer->transfer_date?->format('Y-m-d') ?? '';
        $this->status = $stockTransfer->status->value;
        $this->notes = $stockTransfer->notes ?? '';
    }

    #[Computed]
    public function products()
    {
        return \App\Models\Product::query()->orderBy('name')->get();
    }

    #[Computed]
    public function warehouses()
    {
        return \App\Models\Warehouse::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->stockTransferUpdateRules($this->stockTransfer->id));

        $this->stockTransfer->update($validated);

        Flux::toast(variant: 'success', text: __('Stock transfers updated successfully.'));

        $this->redirect(route('stock-transfers.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit Stock transfers') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="reference_number" :label="__('Reference Number')" required />
        <flux:select wire:model="product_id" :label="__('Product Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->products as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model="from_warehouse_id" :label="__('From Warehouse Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->warehouses as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model="to_warehouse_id" :label="__('To Warehouse Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->warehouses as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="quantity" type="number" :label="__('Quantity')" required />
        <flux:input wire:model="transfer_date" type="date" :label="__('Transfer Date')" required />
        <flux:select wire:model="status" :label="__('Status')">
            @foreach (StockTransferStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('stock-transfers.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
