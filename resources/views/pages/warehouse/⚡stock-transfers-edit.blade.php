<?php

use App\Concerns\StockTransferValidationRules;
use App\Models\StockTransfer;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\Inventory\StockTransferWorkflowService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
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

    public int $quantity = 1;

    public string $transfer_date = '';

    public string $notes = '';

    public function mount(StockTransfer $stockTransfer): void
    {
        $this->authorize('view', $stockTransfer);
        $this->stockTransfer = $stockTransfer->fresh() ?? $stockTransfer;
        $this->syncForm();
    }

    protected function syncForm(): void
    {
        $transfer = $this->stockTransfer;
        $this->reference_number = $transfer->reference_number ?? '';
        $this->product_id = $transfer->product_id;
        $this->from_warehouse_id = $transfer->from_warehouse_id;
        $this->to_warehouse_id = $transfer->to_warehouse_id;
        $this->quantity = (int) $transfer->quantity;
        $this->transfer_date = $transfer->transfer_date?->format('Y-m-d') ?? '';
        $this->notes = $transfer->notes ?? '';
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
        $this->authorize('update', $this->stockTransfer);
        $validated = $this->validate($this->stockTransferUpdateRules($this->stockTransfer->id));
        $this->stockTransfer = $workflow->updateDraft($this->stockTransfer, $validated, Auth::user());
        Flux::toast(variant: 'success', text: __('Stock transfer updated.'));
        $this->redirect(route('stock-transfers.index'), navigate: true);
    }

    public function approve(StockTransferWorkflowService $workflow): void
    {
        $this->authorize('approve', $this->stockTransfer);
        try {
            $this->stockTransfer = $workflow->approve($this->stockTransfer, Auth::user());
            $this->syncForm();
            Flux::toast(variant: 'success', text: __('Transfer approved.'));
        } catch (\Throwable $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    public function ship(StockTransferWorkflowService $workflow): void
    {
        $this->authorize('ship', $this->stockTransfer);
        try {
            $this->stockTransfer = $workflow->ship($this->stockTransfer, Auth::user());
            $this->syncForm();
            Flux::toast(variant: 'success', text: __('Transfer shipped.'));
        } catch (\Throwable $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    public function receive(StockTransferWorkflowService $workflow): void
    {
        $this->authorize('receive', $this->stockTransfer);
        try {
            $this->stockTransfer = $workflow->receive($this->stockTransfer, Auth::user());
            $this->syncForm();
            Flux::toast(variant: 'success', text: __('Transfer received.'));
        } catch (\Throwable $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    public function cancelTransfer(StockTransferWorkflowService $workflow): void
    {
        $this->authorize('cancel', $this->stockTransfer);
        try {
            $this->stockTransfer = $workflow->cancel($this->stockTransfer, Auth::user());
            $this->syncForm();
            Flux::toast(variant: 'success', text: __('Transfer cancelled.'));
        } catch (\Throwable $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit stock transfer') }}</flux:heading>
        <flux:subheading>
            {{ __('Status') }}: {{ $stockTransfer->status->workflowLabel() }}
            ({{ $stockTransfer->status->value }})
        </flux:subheading>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        @can('approve', $stockTransfer)
            <flux:button wire:click="approve" variant="primary">{{ __('Approve') }}</flux:button>
        @endcan
        @can('ship', $stockTransfer)
            <flux:button wire:click="ship" variant="primary">{{ __('Ship') }}</flux:button>
        @endcan
        @can('receive', $stockTransfer)
            <flux:button wire:click="receive" variant="primary">{{ __('Receive') }}</flux:button>
        @endcan
        @can('cancel', $stockTransfer)
            <flux:button wire:click="cancelTransfer" variant="danger">{{ __('Cancel transfer') }}</flux:button>
        @endcan
    </div>

    @if ($stockTransfer->status->isEditable())
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
                <flux:button type="submit" variant="primary">{{ __('Save draft') }}</flux:button>
                <flux:button :href="route('stock-transfers.index')" variant="ghost" wire:navigate>{{ __('Back') }}</flux:button>
            </div>
        </form>
    @else
        <div class="grid max-w-2xl gap-2 text-sm text-zinc-600 dark:text-zinc-300">
            <p>{{ __('Reference') }}: {{ $stockTransfer->reference_number }}</p>
            <p>{{ __('Quantity') }}: {{ $stockTransfer->quantity }}</p>
            <p>{{ __('From') }}: {{ $stockTransfer->fromWarehouse?->name }}</p>
            <p>{{ __('To') }}: {{ $stockTransfer->toWarehouse?->name }}</p>
            <flux:button :href="route('stock-transfers.index')" variant="ghost" wire:navigate>{{ __('Back') }}</flux:button>
        </div>
    @endif
</section>
