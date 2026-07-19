<?php

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Services\Supplier\SupplierPortalService;
use App\Support\ScopesToSupplierContact;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.supplier')] #[Title('Purchase Order')] class extends Component {
    use ScopesToSupplierContact;

    public PurchaseOrder $purchaseOrder;

    public string $comment = '';

    public string $carrier = '';

    public string $tracking_number = '';

    public function mount(PurchaseOrder $purchaseOrder): void
    {
        $this->assertOwns($purchaseOrder);
        abort_if($purchaseOrder->status === PurchaseOrderStatus::Draft, 404);
        $this->purchaseOrder = $purchaseOrder->load(['warehouse', 'shipments']);
        $this->comment = (string) ($purchaseOrder->supplier_comment ?? '');
    }

    public function accept(SupplierPortalService $portal): void
    {
        $this->purchaseOrder = $portal->acceptPurchaseOrder($this->purchaseOrder, auth('supplier')->user(), $this->comment ?: null);
        Flux::toast(variant: 'success', text: __('scf.supplier_portal.po_accepted'));
    }

    public function reject(SupplierPortalService $portal): void
    {
        $this->purchaseOrder = $portal->rejectPurchaseOrder($this->purchaseOrder, auth('supplier')->user(), $this->comment ?: null);
        Flux::toast(variant: 'success', text: __('scf.supplier_portal.po_rejected'));
    }

    public function saveComment(SupplierPortalService $portal): void
    {
        $this->validate(['comment' => ['required', 'string', 'max:2000']]);
        $this->purchaseOrder = $portal->commentOnPurchaseOrder($this->purchaseOrder, auth('supplier')->user(), $this->comment);
        Flux::toast(variant: 'success', text: __('scf.supplier_portal.po_commented'));
    }

    public function confirmShipment(SupplierPortalService $portal): void
    {
        $portal->confirmShipment($this->purchaseOrder, auth('supplier')->user(), [
            'carrier' => $this->carrier ?: null,
            'tracking_number' => $this->tracking_number ?: null,
            'notes' => $this->comment ?: null,
        ]);
        $this->purchaseOrder->refresh()->load('shipments');
        Flux::toast(variant: 'success', text: __('scf.supplier_portal.shipment_confirmed'));
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="lg">{{ $purchaseOrder->reference_number }}</flux:heading>
                <p class="mt-1 text-sm text-zinc-500">{{ __('scf.supplier_portal.order_details') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:badge :color="$purchaseOrder->status->color()">{{ $purchaseOrder->status->label() }}</flux:badge>
                <flux:button size="sm" :href="route('supplier.print', ['type' => 'purchase-order', 'id' => $purchaseOrder->id])" variant="ghost" target="_blank" icon="printer">{{ __('Print') }}</flux:button>
                <flux:button size="sm" :href="route('supplier.pdf', ['type' => 'purchase-order', 'id' => $purchaseOrder->id])" variant="ghost" target="_blank" icon="arrow-down-tray">PDF</flux:button>
            </div>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-xs uppercase text-zinc-500">{{ __('Order date') }}</p>
                <p class="mt-1 font-medium">{{ $purchaseOrder->order_date?->format('Y-m-d') }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">{{ __('Expected') }}</p>
                <p class="mt-1 font-medium">{{ $purchaseOrder->expected_date?->format('Y-m-d') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">{{ __('Warehouse') }}</p>
                <p class="mt-1 font-medium">{{ $purchaseOrder->warehouse?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">{{ __('Total') }}</p>
                <p class="mt-1 font-medium tabular-nums">{{ number_format((float) $purchaseOrder->total_amount, 2) }}</p>
            </div>
        </div>

        @if ($purchaseOrder->notes)
            <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">{{ $purchaseOrder->notes }}</p>
        @endif
    </div>

    <div class="portal-glass rounded-2xl p-6 space-y-4">
        <flux:heading size="sm">{{ __('scf.supplier_portal.supplier_actions') }}</flux:heading>
        <flux:textarea wire:model="comment" :label="__('Comment')" rows="3" />
        <div class="flex flex-wrap gap-2">
            <flux:button wire:click="saveComment" variant="ghost">{{ __('Save comment') }}</flux:button>
            @if ($purchaseOrder->status === PurchaseOrderStatus::Confirmed && $purchaseOrder->supplier_response === null)
                <flux:button wire:click="accept" variant="primary">{{ __('Accept order') }}</flux:button>
                <flux:button wire:click="reject" variant="danger">{{ __('Reject order') }}</flux:button>
            @endif
        </div>
    </div>

    @if ($purchaseOrder->supplier_response?->value === 'accepted' || $purchaseOrder->status === PurchaseOrderStatus::Confirmed)
        <div class="portal-glass rounded-2xl p-6 space-y-4">
            <flux:heading size="sm">{{ __('scf.supplier_portal.confirm_shipment') }}</flux:heading>
            <div class="grid gap-3 sm:grid-cols-2">
                <flux:input wire:model="carrier" :label="__('Carrier')" />
                <flux:input wire:model="tracking_number" :label="__('Tracking number')" />
            </div>
            <flux:button wire:click="confirmShipment" variant="primary" icon="truck">{{ __('Confirm shipment') }}</flux:button>
        </div>
    @endif

    @if ($purchaseOrder->shipments->isNotEmpty())
        <div class="portal-glass rounded-2xl p-6">
            <flux:heading size="sm">{{ __('scf.supplier_portal.delivery_history') }}</flux:heading>
            <ul class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($purchaseOrder->shipments as $shipment)
                    <li class="flex items-center justify-between gap-3 py-3">
                        <div>
                            <p class="font-medium">{{ $shipment->reference_number }}</p>
                            <p class="text-xs text-zinc-500">{{ $shipment->carrier }} · {{ $shipment->tracking_number }}</p>
                        </div>
                        <flux:badge size="sm" :color="$shipment->status->color()">{{ $shipment->status->label() }}</flux:badge>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</section>
