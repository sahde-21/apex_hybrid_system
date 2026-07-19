<?php

use App\Models\PurchaseOrder;
use App\Models\SupplierShipment;
use App\Services\Supplier\SupplierPortalService;
use App\Support\ScopesToSupplierContact;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.supplier')] #[Title('Deliveries')] class extends Component {
    use ScopesToSupplierContact;
    use WithPagination;

    #[Url]
    public string $tab = 'schedule';

    public ?int $confirmOrderId = null;

    public string $carrier = '';

    public string $tracking_number = '';

    public function openConfirm(int $orderId): void
    {
        $order = PurchaseOrder::query()->findOrFail($orderId);
        $this->assertOwns($order);
        $this->confirmOrderId = $orderId;
    }

    public function confirmShipment(SupplierPortalService $portal): void
    {
        abort_if($this->confirmOrderId === null, 422);
        $order = PurchaseOrder::query()->findOrFail($this->confirmOrderId);
        $this->assertOwns($order);
        $portal->confirmShipment($order, auth('supplier')->user(), [
            'carrier' => $this->carrier ?: null,
            'tracking_number' => $this->tracking_number ?: null,
        ]);
        $this->reset('confirmOrderId', 'carrier', 'tracking_number');
        Flux::toast(variant: 'success', text: __('scf.supplier_portal.shipment_confirmed'));
    }

    #[Computed]
    public function schedule()
    {
        return $this->scopeOwned(PurchaseOrder::query())
            ->whereIn('status', ['confirmed', 'received'])
            ->whereNotNull('expected_date')
            ->orderBy('expected_date')
            ->paginate(10);
    }

    #[Computed]
    public function shipments()
    {
        return $this->scopeOwned(SupplierShipment::query())
            ->with('purchaseOrder')
            ->latest()
            ->paginate(10);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <flux:heading size="lg">{{ __('scf.supplier_portal.deliveries') }}</flux:heading>
        <p class="mt-1 text-sm text-zinc-500">{{ __('scf.supplier_portal.deliveries_subtitle') }}</p>
        <div class="mt-4 flex gap-2">
            <flux:button size="sm" wire:click="$set('tab', 'schedule')" :variant="$tab === 'schedule' ? 'primary' : 'ghost'">{{ __('Schedule') }}</flux:button>
            <flux:button size="sm" wire:click="$set('tab', 'history')" :variant="$tab === 'history' ? 'primary' : 'ghost'">{{ __('History') }}</flux:button>
        </div>
    </div>

    @if ($tab === 'schedule')
        <div class="portal-glass overflow-hidden rounded-2xl">
            <flux:table :paginate="$this->schedule">
                <flux:table.columns>
                    <flux:table.column>{{ __('PO') }}</flux:table.column>
                    <flux:table.column>{{ __('Expected') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->schedule as $order)
                        <flux:table.row wire:key="sched-{{ $order->id }}">
                            <flux:table.cell class="font-medium">{{ $order->reference_number }}</flux:table.cell>
                            <flux:table.cell>{{ $order->expected_date?->format('Y-m-d') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$order->status->color()">{{ $order->status->label() }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="xs" wire:click="openConfirm({{ $order->id }})" variant="primary">{{ __('Confirm shipment') }}</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4">
                                <x-empty-state icon="truck" :title="__('scf.supplier_portal.no_deliveries')" />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    @else
        <div class="portal-glass overflow-hidden rounded-2xl">
            <flux:table :paginate="$this->shipments">
                <flux:table.columns>
                    <flux:table.column>{{ __('Reference') }}</flux:table.column>
                    <flux:table.column>{{ __('PO') }}</flux:table.column>
                    <flux:table.column>{{ __('Shipped') }}</flux:table.column>
                    <flux:table.column>{{ __('Tracking') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->shipments as $shipment)
                        <flux:table.row wire:key="ship-{{ $shipment->id }}">
                            <flux:table.cell class="font-medium">{{ $shipment->reference_number }}</flux:table.cell>
                            <flux:table.cell>{{ $shipment->purchaseOrder?->reference_number }}</flux:table.cell>
                            <flux:table.cell>{{ $shipment->shipped_at?->format('Y-m-d H:i') ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $shipment->tracking_number ?? '—' }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$shipment->status->color()">{{ $shipment->status->label() }}</flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5">
                                <x-empty-state icon="truck" :title="__('scf.supplier_portal.no_deliveries')" />
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    @endif

    @if ($confirmOrderId)
        <div class="portal-glass rounded-2xl p-6 space-y-4">
            <flux:heading size="sm">{{ __('scf.supplier_portal.confirm_shipment') }}</flux:heading>
            <div class="grid gap-3 sm:grid-cols-2">
                <flux:input wire:model="carrier" :label="__('Carrier')" />
                <flux:input wire:model="tracking_number" :label="__('Tracking number')" />
            </div>
            <div class="flex gap-2">
                <flux:button wire:click="confirmShipment" variant="primary">{{ __('Confirm') }}</flux:button>
                <flux:button wire:click="$set('confirmOrderId', null)" variant="ghost">{{ __('Cancel') }}</flux:button>
            </div>
        </div>
    @endif
</section>
