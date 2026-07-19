<?php

use App\Models\SaleOrder;
use App\Support\ScopesToPortalContact;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.portal')] #[Title('Order details')] class extends Component {
    use ScopesToPortalContact;

    public SaleOrder $saleOrder;

    public function mount(SaleOrder $saleOrder): void
    {
        $this->assertOwns($saleOrder);
        $this->saleOrder = $saleOrder->load(['contact', 'warehouse']);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="lg">{{ $saleOrder->reference_number }}</flux:heading>
                <flux:subheading class="mt-1">{{ __('scf.portal.order_details') }}</flux:subheading>
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:button :href="route('portal.print', ['type' => 'sale-order', 'id' => $saleOrder->id])" icon="printer" size="sm" target="_blank">
                    {{ __('scf.print_a4') }}
                </flux:button>
                <flux:button :href="route('portal.orders.index')" variant="ghost" size="sm" wire:navigate>{{ __('Back') }}</flux:button>
            </div>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-xs text-zinc-500">{{ __('Status') }}</p>
                <p class="mt-1 font-medium capitalize">{{ $saleOrder->status instanceof \BackedEnum ? $saleOrder->status->value : $saleOrder->status }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-500">{{ __('Order date') }}</p>
                <p class="mt-1 font-medium">{{ $saleOrder->order_date?->format('Y-m-d') }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-500">{{ __('Delivery date') }}</p>
                <p class="mt-1 font-medium">{{ $saleOrder->delivery_date?->format('Y-m-d') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-500">{{ __('Total') }}</p>
                <p class="mt-1 text-lg font-semibold tabular-nums">{{ number_format((float) $saleOrder->total_amount, 2) }}</p>
            </div>
        </div>

        @if ($saleOrder->notes)
            <div class="mt-6 rounded-xl bg-zinc-50 p-4 text-sm dark:bg-zinc-800/50">
                {{ $saleOrder->notes }}
            </div>
        @endif
    </div>
</section>
