<?php

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Services\Supplier\SupplierPortalService;
use App\Support\ScopesToSupplierContact;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.supplier')] #[Title('Purchase Orders')] class extends Component {
    use ScopesToSupplierContact;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function accept(int $id, SupplierPortalService $portal): void
    {
        $order = PurchaseOrder::query()->findOrFail($id);
        $this->assertOwns($order);
        $portal->acceptPurchaseOrder($order, auth('supplier')->user());
        Flux::toast(variant: 'success', text: __('scf.supplier_portal.po_accepted'));
    }

    public function reject(int $id, SupplierPortalService $portal): void
    {
        $order = PurchaseOrder::query()->findOrFail($id);
        $this->assertOwns($order);
        $portal->rejectPurchaseOrder($order, auth('supplier')->user());
        Flux::toast(variant: 'success', text: __('scf.supplier_portal.po_rejected'));
    }

    #[Computed]
    public function orders()
    {
        return $this->scopeOwned(PurchaseOrder::query())
            ->when($this->search, fn ($q) => $q->where('reference_number', 'like', "%{$this->search}%"))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->where('status', '!=', PurchaseOrderStatus::Draft)
            ->latest('order_date')
            ->paginate(10);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <flux:heading size="lg">{{ __('scf.supplier_portal.purchase_orders') }}</flux:heading>
        <p class="mt-1 text-sm text-zinc-500">{{ __('scf.supplier_portal.purchase_orders_subtitle') }}</p>
        <div class="mt-4 flex flex-wrap gap-3">
            <flux:input class="max-w-md" wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />
            <flux:select class="w-48" wire:model.live="status">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (PurchaseOrderStatus::cases() as $status)
                    @if ($status !== PurchaseOrderStatus::Draft)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endif
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="portal-glass overflow-hidden rounded-2xl">
        <flux:table :paginate="$this->orders">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Expected') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Response') }}</flux:table.column>
                <flux:table.column>{{ __('Total') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->orders as $order)
                    <flux:table.row wire:key="po-{{ $order->id }}">
                        <flux:table.cell class="font-medium">
                            <a href="{{ route('supplier.purchase-orders.show', $order) }}" class="hover:underline" wire:navigate>
                                {{ $order->reference_number }}
                            </a>
                        </flux:table.cell>
                        <flux:table.cell>{{ $order->order_date?->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>{{ $order->expected_date?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$order->status->color()">{{ $order->status->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($order->supplier_response)
                                <flux:badge size="sm" :color="$order->supplier_response->color()">{{ $order->supplier_response->label() }}</flux:badge>
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums">{{ number_format((float) $order->total_amount, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1">
                                <flux:button size="xs" :href="route('supplier.purchase-orders.show', $order)" variant="ghost" wire:navigate>{{ __('View') }}</flux:button>
                                <flux:button size="xs" :href="route('supplier.pdf', ['type' => 'purchase-order', 'id' => $order->id])" variant="ghost" target="_blank">PDF</flux:button>
                                @if ($order->status === PurchaseOrderStatus::Confirmed && $order->supplier_response === null)
                                    <flux:button size="xs" wire:click="accept({{ $order->id }})" variant="primary">{{ __('Accept') }}</flux:button>
                                    <flux:button size="xs" wire:click="reject({{ $order->id }})" variant="danger">{{ __('Reject') }}</flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <x-empty-state icon="clipboard-document-list" :title="__('scf.supplier_portal.no_purchase_orders')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</section>
