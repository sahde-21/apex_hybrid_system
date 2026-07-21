<?php

use App\Models\AccountingPosting;
use App\Models\Bill;
use App\Services\Purchasing\BillWorkflowService;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Bill')] class extends Component {
    public Bill $bill;

    public string $voidReason = '';
    public bool $showVoidModal = false;
    public string $cancelReason = '';
    public bool $showCancelModal = false;

    public function mount(Bill $bill): void
    {
        $this->authorize('view', $bill);
        $this->bill = $bill->load([
            'contact',
            'lines.product',
            'purchaseOrder.rfq',
            'purchaseOrder.purchaseRequest',
            'payments',
            'events.user',
        ]);

        app(BillWorkflowService::class)->markOverdueIfNeeded($bill);
        $this->bill->refresh();
    }

    #[Computed]
    public function journalEntry()
    {
        $posting = AccountingPosting::query()
            ->where('source_type', $this->bill->getMorphClass())
            ->where('source_id', $this->bill->id)
            ->where('event', 'bill.posted')
            ->first();

        return $posting?->journalEntry;
    }

    #[Computed]
    public function balanceDue(): float
    {
        return app(BillWorkflowService::class)->balanceDue($this->bill);
    }

    public function issue(): void
    {
        try {
            app(BillWorkflowService::class)->issue($this->bill, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.bill_issued'));
            $this->bill->refresh();
            unset($this->journalEntry);
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function openVoidModal(): void
    {
        $this->voidReason = '';
        $this->showVoidModal = true;
    }

    public function void(): void
    {
        $this->validate(['voidReason' => 'nullable|string|max:500']);
        try {
            app(BillWorkflowService::class)->void($this->bill, auth()->user(), $this->voidReason ?: null);
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.bill_voided'));
            $this->showVoidModal = false;
            $this->bill->refresh();
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function openCancelModal(): void
    {
        $this->cancelReason = '';
        $this->showCancelModal = true;
    }

    public function cancel(): void
    {
        $this->validate(['cancelReason' => 'nullable|string|max:500']);
        try {
            app(BillWorkflowService::class)->cancel($this->bill, auth()->user(), $this->cancelReason ?: null);
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.bill_cancelled'));
            $this->showCancelModal = false;
            $this->bill->refresh();
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }
}; ?>

<section class="scf-page">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl">{{ $bill->reference_number }}</flux:heading>
                <flux:badge :color="$bill->status->color()">{{ $bill->status->label() }}</flux:badge>
            </div>
            <flux:subheading class="mt-1">
                {{ $bill->contact?->name ?? __('No vendor') }} ·
                {{ $bill->bill_date->format('d M Y') }}
            </flux:subheading>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('update', $bill)
                @if ($bill->status->isEditable())
                    <flux:button :href="route('bills.edit', $bill)" icon="pencil-square" variant="primary" wire:navigate>
                        {{ __('Edit') }}
                    </flux:button>
                @endif
            @endcan

            @can('bills.issue')
                @if ($bill->status->canTransitionTo(\App\Enums\BillStatus::Received))
                    <flux:button wire:click="issue" icon="check-circle" variant="filled">{{ __('Issue') }}</flux:button>
                @endif
            @endcan

            @can('bills.void')
                @if ($bill->status->canTransitionTo(\App\Enums\BillStatus::Void))
                    <flux:button wire:click="openVoidModal" icon="archive-box-x-mark" variant="ghost">{{ __('Void') }}</flux:button>
                @endif
            @endcan

            @can('update', $bill)
                @if ($bill->status === \App\Enums\BillStatus::Draft)
                    <flux:button wire:click="openCancelModal" icon="x-mark" variant="ghost">{{ __('Cancel') }}</flux:button>
                @endif
            @endcan

            <flux:button :href="route('bills.index')" variant="ghost" wire:navigate>{{ __('Back') }}</flux:button>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="scf-card space-y-3 lg:col-span-1">
            <flux:heading size="lg">{{ __('Details') }}</flux:heading>
            <div class="space-y-2 text-sm">
                <p><span class="text-zinc-500">{{ __('Vendor') }}:</span> {{ $bill->contact?->name ?? '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Bill date') }}:</span> {{ $bill->bill_date->format('d M Y') }}</p>
                <p><span class="text-zinc-500">{{ __('Due date') }}:</span> {{ $bill->due_date?->format('d M Y') ?? '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Currency') }}:</span> {{ $bill->currency_code }}</p>
                @if ($bill->issued_at)
                    <p><span class="text-zinc-500">{{ __('Issued at') }}:</span> {{ $bill->issued_at->format('d M Y H:i') }}</p>
                @endif
                @if ($bill->void_reason)
                    <p><span class="text-zinc-500">{{ __('Void reason') }}:</span> {{ $bill->void_reason }}</p>
                @endif
            </div>

            {{-- Payment summary --}}
            <div class="border-t border-zinc-100 pt-3 dark:border-zinc-800 space-y-1 text-sm">
                <div class="flex justify-between">
                    <span class="text-zinc-500">{{ __('Total') }}</span>
                    <span class="font-medium">{{ $bill->currency_code }} {{ number_format((float) $bill->total_amount, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-500">{{ __('Paid') }}</span>
                    <span class="font-medium text-green-600">{{ number_format((float) $bill->paid_amount, 2) }}</span>
                </div>
                <div class="flex justify-between border-t border-zinc-100 pt-1 dark:border-zinc-800">
                    <span class="font-semibold">{{ __('Balance due') }}</span>
                    <span class="font-semibold {{ $this->balanceDue > 0 ? 'text-red-600' : 'text-green-600' }}">
                        {{ number_format($this->balanceDue, 2) }}
                    </span>
                </div>
            </div>

            @if ($bill->notes)
                <div class="border-t border-zinc-100 pt-3 dark:border-zinc-800">
                    <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ __('Notes') }}</p>
                    <p class="mt-1 text-sm">{{ $bill->notes }}</p>
                </div>
            @endif
        </div>

        <div class="space-y-6 lg:col-span-2">
            <div class="scf-card">
                <flux:heading size="lg" class="mb-4">{{ __('Line items') }}</flux:heading>
                @if ($bill->lines->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700 text-left">
                                    <th class="pb-2 pr-4 font-medium text-zinc-500">{{ __('Description') }}</th>
                                    <th class="pb-2 pr-4 font-medium text-zinc-500 text-right">{{ __('Qty') }}</th>
                                    <th class="pb-2 pr-4 font-medium text-zinc-500 text-right">{{ __('Unit price') }}</th>
                                    <th class="pb-2 pr-4 font-medium text-zinc-500 text-right">{{ __('Tax') }}</th>
                                    <th class="pb-2 font-medium text-zinc-500 text-right">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($bill->lines as $line)
                                    <tr>
                                        <td class="py-2 pr-4">
                                            @if ($line->product)<p class="font-medium">{{ $line->product->name }}</p>@endif
                                            @if ($line->description)<p class="text-xs text-zinc-500">{{ $line->description }}</p>@endif
                                        </td>
                                        <td class="py-2 pr-4 text-right">{{ $line->quantity }}</td>
                                        <td class="py-2 pr-4 text-right">{{ number_format((float) $line->unit_price, 2) }}</td>
                                        <td class="py-2 pr-4 text-right">{{ number_format((float) $line->tax_amount, 2) }}</td>
                                        <td class="py-2 text-right font-medium">{{ number_format((float) $line->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t border-zinc-200 dark:border-zinc-700">
                                <tr>
                                    <td colspan="4" class="pt-3 pr-4 text-right text-zinc-500">{{ __('Subtotal') }}</td>
                                    <td class="pt-3 text-right">{{ number_format((float) $bill->subtotal_amount, 2) }}</td>
                                </tr>
                                @if ((float) $bill->discount_amount > 0)
                                    <tr>
                                        <td colspan="4" class="pt-1 pr-4 text-right text-zinc-500">{{ __('Discount') }}</td>
                                        <td class="pt-1 text-right text-red-600">−{{ number_format((float) $bill->discount_amount, 2) }}</td>
                                    </tr>
                                @endif
                                @if ((float) $bill->tax_amount > 0)
                                    <tr>
                                        <td colspan="4" class="pt-1 pr-4 text-right text-zinc-500">{{ __('Tax') }}</td>
                                        <td class="pt-1 text-right">{{ number_format((float) $bill->tax_amount, 2) }}</td>
                                    </tr>
                                @endif
                                <tr class="font-semibold">
                                    <td colspan="4" class="pt-2 pr-4 text-right">{{ __('Total') }}</td>
                                    <td class="pt-2 text-right">{{ $bill->currency_code }} {{ number_format((float) $bill->total_amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-zinc-500">{{ __('No line items.') }}</p>
                @endif
            </div>

            {{-- Payments list --}}
            @if ($bill->payments->isNotEmpty())
                <div class="scf-card">
                    <flux:heading size="lg" class="mb-4">{{ __('Payments') }}</flux:heading>
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($bill->payments as $pmt)
                            <div class="flex items-center justify-between py-3">
                                <div class="text-sm">
                                    @can('view', $pmt)
                                        <a href="{{ route('payments.show', $pmt) }}" wire:navigate class="font-medium hover:underline">
                                            {{ $pmt->reference_number }}
                                        </a>
                                    @else
                                        <span class="font-medium">{{ $pmt->reference_number }}</span>
                                    @endcan
                                    <p class="text-xs text-zinc-500">{{ $pmt->payment_date->format('d M Y') }} · {{ $pmt->payment_method ?? '—' }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <flux:badge size="sm" :color="$pmt->status->color()">{{ $pmt->status->label() }}</flux:badge>
                                    <span class="text-sm font-medium">{{ number_format((float) $pmt->amount, 2) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <x-purchasing.related-documents
                :purchase-request="$bill->purchaseOrder?->purchaseRequest"
                :rfq="$bill->purchaseOrder?->rfq"
                :purchase-order="$bill->purchaseOrder"
                :bill="null"
                :payment="$bill->payments->first()"
                :journal-entry="$this->journalEntry"
            />

            <livewire:activity.activity-timeline :subject="$bill" :key="'activity-bill-'.$bill->id" />
        </div>
    </div>

    <flux:modal wire:model="showVoidModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Void bill') }}</flux:heading>
            <flux:text class="text-sm text-zinc-600">{{ __('Voiding will reverse the journal entry. This cannot be undone.') }}</flux:text>
            <flux:textarea wire:model="voidReason" :label="__('Reason (optional)')" rows="3" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button variant="danger" wire:click="void">{{ __('Void bill') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showCancelModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Cancel bill') }}</flux:heading>
            <flux:textarea wire:model="cancelReason" :label="__('Reason (optional)')" rows="3" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Back') }}</flux:button></flux:modal.close>
                <flux:button variant="danger" wire:click="cancel">{{ __('Cancel bill') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
