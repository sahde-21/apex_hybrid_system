<?php

use App\Concerns\StockTransferValidationRules;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\Inventory\StockTransferWorkflowService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Stock transfers')] class extends Component {
    use StockTransferValidationRules;

    public string $reference_number = '';

    public ?int $product_id = null;

    public ?int $from_warehouse_id = null;

    public ?int $to_warehouse_id = null;

    public int $quantity = 1;

    public string $transfer_date = '';

    public string $notes = '';

    public function mount(): void
    {
        $this->authorize('create', \App\Models\StockTransfer::class);
        $this->transfer_date = now()->format('Y-m-d');
    }

    #[Computed]
    public function products()
    {
        return Product::query()->where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function warehouses()
    {
        return Warehouse::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function save(StockTransferWorkflowService $workflow): void
    {
        $validated = $this->validate($this->stockTransferRules());
        $workflow->createDraft($validated, Auth::user());
        Flux::toast(variant: 'success', text: __('Stock transfer draft created.'));
        $this->redirect(route('stock-transfers.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create stock transfer') }}</flux:heading>
        <flux:subheading>{{ __('Draft only. Approve → Ship → Receive to move stock when ledger is enabled.') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="reference_number" :label="__('Reference number')" required />
        <flux:select wire:model="product_id" :label="__('Product')" :placeholder="__('Select')" required>
            @foreach ($this->products as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model="from_warehouse_id" :label="__('From warehouse')" :placeholder="__('Select')" required>
            @foreach ($this->warehouses as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model="to_warehouse_id" :label="__('To warehouse')" :placeholder="__('Select')" required>
            @foreach ($this->warehouses as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="quantity" type="number" min="1" :label="__('Quantity')" required />
        <flux:input wire:model="transfer_date" type="date" :label="__('Transfer date')" required />
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create draft') }}</flux:button>
            <flux:button :href="route('stock-transfers.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
