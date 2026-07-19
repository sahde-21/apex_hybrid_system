@props([
    'quotation' => null,
    'saleOrder' => null,
    'invoice' => null,
    'payment' => null,
    'journalEntry' => null,
])

@php
    $chain = array_filter([
        'quotation' => $quotation,
        'saleOrder' => $saleOrder,
        'invoice' => $invoice,
        'payment' => $payment,
        'journalEntry' => $journalEntry,
    ]);
@endphp

@if (count($chain) > 0)
    <div class="scf-card">
        <flux:heading size="sm" class="mb-3">{{ __('Related documents') }}</flux:heading>
        <div class="flex flex-wrap items-center gap-2">
            @if ($quotation)
                @can('view', $quotation)
                    <a href="{{ route('quotations.show', $quotation) }}" wire:navigate
                       class="flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                        <flux:icon name="document-text" class="size-4 text-zinc-400" />
                        <div>
                            <p class="font-medium leading-tight">{{ $quotation->reference_number }}</p>
                            <div class="mt-0.5 flex items-center gap-1">
                                <flux:badge size="sm" :color="$quotation->status->color()">{{ $quotation->status->label() }}</flux:badge>
                                @if ($quotation->quotation_date)
                                    <span class="text-xs text-zinc-500">{{ $quotation->quotation_date->format('d M Y') }}</span>
                                @endif
                            </div>
                            <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                {{ number_format((float) $quotation->total_amount, 2) }}
                            </p>
                        </div>
                    </a>
                @endcan
                @if ($saleOrder || $invoice || $payment || $journalEntry)
                    <flux:icon name="arrow-right" class="size-4 shrink-0 text-zinc-400" />
                @endif
            @endif

            @if ($saleOrder)
                @can('view', $saleOrder)
                    <a href="{{ route('sale-orders.show', $saleOrder) }}" wire:navigate
                       class="flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                        <flux:icon name="shopping-bag" class="size-4 text-zinc-400" />
                        <div>
                            <p class="font-medium leading-tight">{{ $saleOrder->reference_number }}</p>
                            <div class="mt-0.5 flex items-center gap-1">
                                <flux:badge size="sm" :color="$saleOrder->status->color()">{{ $saleOrder->status->label() }}</flux:badge>
                                @if ($saleOrder->order_date)
                                    <span class="text-xs text-zinc-500">{{ $saleOrder->order_date->format('d M Y') }}</span>
                                @endif
                            </div>
                            <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                {{ number_format((float) $saleOrder->total_amount, 2) }}
                            </p>
                        </div>
                    </a>
                @endcan
                @if ($invoice || $payment || $journalEntry)
                    <flux:icon name="arrow-right" class="size-4 shrink-0 text-zinc-400" />
                @endif
            @endif

            @if ($invoice)
                @can('view', $invoice)
                    <a href="{{ route('invoices.show', $invoice) }}" wire:navigate
                       class="flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                        <flux:icon name="document-currency-dollar" class="size-4 text-zinc-400" />
                        <div>
                            <p class="font-medium leading-tight">{{ $invoice->reference_number }}</p>
                            <div class="mt-0.5 flex items-center gap-1">
                                <flux:badge size="sm" :color="$invoice->status->color()">{{ $invoice->status->label() }}</flux:badge>
                                @if ($invoice->invoice_date)
                                    <span class="text-xs text-zinc-500">{{ $invoice->invoice_date->format('d M Y') }}</span>
                                @endif
                            </div>
                            <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                {{ number_format((float) $invoice->total_amount, 2) }}
                            </p>
                        </div>
                    </a>
                @endcan
                @if ($payment || $journalEntry)
                    <flux:icon name="arrow-right" class="size-4 shrink-0 text-zinc-400" />
                @endif
            @endif

            @if ($payment)
                @can('view', $payment)
                    <a href="{{ route('payments.show', $payment) }}" wire:navigate
                       class="flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                        <flux:icon name="banknotes" class="size-4 text-zinc-400" />
                        <div>
                            <p class="font-medium leading-tight">{{ $payment->reference_number }}</p>
                            <div class="mt-0.5 flex items-center gap-1">
                                <flux:badge size="sm" :color="$payment->status->color()">{{ $payment->status->label() }}</flux:badge>
                                @if ($payment->payment_date)
                                    <span class="text-xs text-zinc-500">{{ $payment->payment_date->format('d M Y') }}</span>
                                @endif
                            </div>
                            <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                {{ number_format((float) $payment->amount, 2) }}
                            </p>
                        </div>
                    </a>
                @endcan
                @if ($journalEntry)
                    <flux:icon name="arrow-right" class="size-4 shrink-0 text-zinc-400" />
                @endif
            @endif

            @if ($journalEntry)
                @can('journal-entries.read')
                    @if (Route::has('journal-entries.edit'))
                        <a href="{{ route('journal-entries.edit', $journalEntry) }}" wire:navigate
                           class="flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                            <flux:icon name="book-open" class="size-4 text-zinc-400" />
                            <div>
                                <p class="font-medium leading-tight">
                                    {{ $journalEntry->reference_number ?? ('JE-' . $journalEntry->id) }}
                                </p>
                                @if ($journalEntry->entry_date)
                                    <span class="text-xs text-zinc-500">{{ $journalEntry->entry_date->format('d M Y') }}</span>
                                @endif
                            </div>
                        </a>
                    @else
                        <div class="flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                            <flux:icon name="book-open" class="size-4 text-zinc-400" />
                            <span class="font-medium">{{ $journalEntry->reference_number ?? ('JE-' . $journalEntry->id) }}</span>
                        </div>
                    @endif
                @endcan
            @endif
        </div>
    </div>
@endif
