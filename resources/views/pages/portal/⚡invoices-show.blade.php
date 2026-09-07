<?php

use App\Models\Invoice;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.portal')] #[Title('Invoice')] class extends \App\Livewire\ConcernBases\ScopesToPortalContactBase {

    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        $this->assertOwns($invoice);
        $this->invoice = $invoice->load(['contact', 'payments', 'saleOrder']);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="lg">{{ $invoice->reference_number }}</flux:heading>
                <flux:badge class="mt-2" size="sm" :color="$invoice->status->color()">{{ $invoice->status->label() }}</flux:badge>
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:button :href="route('portal.pdf', ['type' => 'invoice', 'id' => $invoice->id])" icon="arrow-down-tray" size="sm">
                    {{ __('Download PDF') }}
                </flux:button>
                <flux:button :href="route('portal.print', ['type' => 'invoice', 'id' => $invoice->id])" icon="printer" size="sm" target="_blank">
                    {{ __('scf.print_a4') }}
                </flux:button>
                <flux:button :href="route('portal.invoices.index')" variant="ghost" size="sm" wire:navigate>{{ __('Back') }}</flux:button>
            </div>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-xs text-zinc-500">{{ __('Invoice date') }}</p>
                <p class="mt-1 font-medium">{{ $invoice->invoice_date?->format('Y-m-d') }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-500">{{ __('Due date') }}</p>
                <p class="mt-1 font-medium">{{ $invoice->due_date?->format('Y-m-d') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-500">{{ __('Payment status') }}</p>
                <p class="mt-1 font-medium">{{ $invoice->status->label() }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-500">{{ __('Total') }}</p>
                <p class="mt-1 text-lg font-semibold tabular-nums">{{ number_format((float) $invoice->total_amount, 2) }}</p>
            </div>
        </div>

        @if ($invoice->payments->isNotEmpty())
            <div class="mt-8">
                <flux:heading size="md">{{ __('scf.portal.payments') }}</flux:heading>
                <div class="mt-3 divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($invoice->payments as $payment)
                        <div class="flex items-center justify-between py-2 text-sm">
                            <span>{{ $payment->reference_number }} · {{ $payment->payment_date?->format('Y-m-d') }}</span>
                            <span class="tabular-nums font-medium">{{ number_format((float) $payment->amount, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
