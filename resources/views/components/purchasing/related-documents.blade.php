@props([
    'purchaseRequest' => null,
    'rfq' => null,
    'purchaseOrder' => null,
    'bill' => null,
    'payment' => null,
    'journalEntry' => null,
])

@php
    $chain = array_filter([
        'purchaseRequest' => $purchaseRequest,
        'rfq' => $rfq,
        'purchaseOrder' => $purchaseOrder,
        'bill' => $bill,
        'payment' => $payment,
        'journalEntry' => $journalEntry,
    ]);
@endphp

@if (count($chain) > 0)
    <div class="scf-card">
        <flux:heading size="sm" class="mb-3">{{ __('Related documents') }}</flux:heading>
        <div class="flex flex-wrap items-center gap-2">

            @if ($purchaseRequest)
                @can('view', $purchaseRequest)
                    @if (Route::has('purchase-requests.show'))
                        <a href="{{ route('purchase-requests.show', $purchaseRequest) }}" wire:navigate
                           class="flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                            <flux:icon name="clipboard-document-list" class="size-4 text-zinc-400" />
                            <div>
                                <p class="font-medium leading-tight">{{ $purchaseRequest->reference_number }}</p>
                                <div class="mt-0.5 flex items-center gap-1">
                                    <flux:badge size="sm" :color="$purchaseRequest->status->color()">{{ $purchaseRequest->status->label() }}</flux:badge>
                                    @if ($purchaseRequest->request_date)
                                        <span class="text-xs text-zinc-500">{{ $purchaseRequest->request_date->format('d M Y') }}</span>
                                    @endif
                                </div>
                                <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                    {{ number_format((float) $purchaseRequest->total_amount, 2) }}
                                </p>
                            </div>
                        </a>
                    @endif
                @endcan
                @if ($rfq || $purchaseOrder || $bill || $payment || $journalEntry)
                    <flux:icon name="arrow-right" class="size-4 shrink-0 text-zinc-400" />
                @endif
            @endif

            @if ($rfq)
                @can('view', $rfq)
                    @if (Route::has('rfqs.show'))
                        <a href="{{ route('rfqs.show', $rfq) }}" wire:navigate
                           class="flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                            <flux:icon name="envelope-open" class="size-4 text-zinc-400" />
                            <div>
                                <p class="font-medium leading-tight">{{ $rfq->reference_number }}</p>
                                <div class="mt-0.5 flex items-center gap-1">
                                    <flux:badge size="sm" :color="$rfq->status->color()">{{ $rfq->status->label() }}</flux:badge>
                                    @if ($rfq->rfq_date)
                                        <span class="text-xs text-zinc-500">{{ $rfq->rfq_date->format('d M Y') }}</span>
                                    @endif
                                </div>
                                <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                    {{ number_format((float) $rfq->total_amount, 2) }}
                                </p>
                            </div>
                        </a>
                    @endif
                @endcan
                @if ($purchaseOrder || $bill || $payment || $journalEntry)
                    <flux:icon name="arrow-right" class="size-4 shrink-0 text-zinc-400" />
                @endif
            @endif

            @if ($purchaseOrder)
                @can('view', $purchaseOrder)
                    @if (Route::has('purchase-orders.show'))
                        <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" wire:navigate
                           class="flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                            <flux:icon name="truck" class="size-4 text-zinc-400" />
                            <div>
                                <p class="font-medium leading-tight">{{ $purchaseOrder->reference_number }}</p>
                                <div class="mt-0.5 flex items-center gap-1">
                                    <flux:badge size="sm" :color="$purchaseOrder->status->color()">{{ $purchaseOrder->status->label() }}</flux:badge>
                                    @if ($purchaseOrder->order_date)
                                        <span class="text-xs text-zinc-500">{{ $purchaseOrder->order_date->format('d M Y') }}</span>
                                    @endif
                                </div>
                                <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                    {{ number_format((float) $purchaseOrder->total_amount, 2) }}
                                </p>
                            </div>
                        </a>
                    @endif
                @endcan
                @if ($bill || $payment || $journalEntry)
                    <flux:icon name="arrow-right" class="size-4 shrink-0 text-zinc-400" />
                @endif
            @endif

            @if ($bill)
                @can('view', $bill)
                    @if (Route::has('bills.show'))
                        <a href="{{ route('bills.show', $bill) }}" wire:navigate
                           class="flex items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">
                            <flux:icon name="document-text" class="size-4 text-zinc-400" />
                            <div>
                                <p class="font-medium leading-tight">{{ $bill->reference_number }}</p>
                                <div class="mt-0.5 flex items-center gap-1">
                                    <flux:badge size="sm" :color="$bill->status->color()">{{ $bill->status->label() }}</flux:badge>
                                    @if ($bill->bill_date)
                                        <span class="text-xs text-zinc-500">{{ $bill->bill_date->format('d M Y') }}</span>
                                    @endif
                                </div>
                                <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                    {{ number_format((float) $bill->total_amount, 2) }}
                                </p>
                            </div>
                        </a>
                    @endif
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
