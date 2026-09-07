<?php

use App\Models\SaleOrder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

new #[Layout('layouts.portal')] #[Title('Orders')] class extends \App\Livewire\ConcernBases\ScopesToPortalContactBase {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function orders()
    {
        return $this->scopeOwned(SaleOrder::query())
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('notes', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->latest('order_date')
            ->paginate(10);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <flux:heading size="lg">{{ __('scf.portal.orders') }}</flux:heading>
        <flux:subheading class="mt-1">{{ __('scf.portal.orders_subtitle') }}</flux:subheading>
        <div class="mt-4 flex flex-wrap gap-3">
            <flux:input class="min-w-64 flex-1" wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />
            <flux:select wire:model.live="status" class="w-44">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (['draft', 'confirmed', 'delivered', 'cancelled'] as $status)
                    <option value="{{ $status }}">{{ __(ucfirst($status)) }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="portal-glass overflow-hidden rounded-2xl">
        <flux:table :paginate="$this->orders">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Total') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->orders as $order)
                    <flux:table.row wire:key="order-{{ $order->id }}">
                        <flux:table.cell class="font-medium">{{ $order->reference_number }}</flux:table.cell>
                        <flux:table.cell>{{ $order->order_date?->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="zinc">{{ $order->status instanceof \BackedEnum ? $order->status->value : $order->status }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums">{{ number_format((float) $order->total_amount, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="sm" variant="ghost" :href="route('portal.orders.show', $order)" wire:navigate>{{ __('View') }}</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <x-empty-state icon="shopping-bag" :title="__('scf.portal.no_orders')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</section>
