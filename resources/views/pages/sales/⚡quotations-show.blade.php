<?php

use App\Models\AccountingPosting;
use App\Models\Quotation;
use App\Services\Sales\QuotationWorkflowService;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Quotation')] class extends Component {
    public Quotation $quotation;

    public string $rejectReason = '';
    public bool $showRejectModal = false;
    public bool $showCancelModal = false;
    public string $cancelReason = '';

    public function mount(Quotation $quotation): void
    {
        $this->authorize('view', $quotation);
        $this->quotation = $quotation->load([
            'contact',
            'lines.product',
            'convertedSaleOrder',
            'events.user',
            'salesperson',
        ]);

        app(QuotationWorkflowService::class)->expireIfNeeded($quotation, auth()->user());
        $this->quotation->refresh();
    }

    #[Computed]
    public function journalEntry()
    {
        $posting = AccountingPosting::query()
            ->where('source_type', $this->quotation->getMorphClass())
            ->where('source_id', $this->quotation->id)
            ->first();

        return $posting?->journalEntry;
    }

    public function send(): void
    {
        try {
            app(QuotationWorkflowService::class)->send($this->quotation, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.sales_workflow.quotation_sent'));
            $this->quotation->refresh();
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function approve(): void
    {
        try {
            app(QuotationWorkflowService::class)->approve($this->quotation, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.sales_workflow.quotation_approved'));
            $this->quotation->refresh();
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function openRejectModal(): void
    {
        $this->rejectReason = '';
        $this->showRejectModal = true;
    }

    public function reject(): void
    {
        $this->validate(['rejectReason' => 'nullable|string|max:500']);
        try {
            app(QuotationWorkflowService::class)->reject($this->quotation, auth()->user(), $this->rejectReason ?: null);
            Flux::toast(variant: 'success', text: __('scf.sales_workflow.quotation_rejected'));
            $this->showRejectModal = false;
            $this->quotation->refresh();
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
            app(QuotationWorkflowService::class)->cancel($this->quotation, auth()->user(), $this->cancelReason ?: null);
            Flux::toast(variant: 'success', text: __('scf.sales_workflow.quotation_cancelled'));
            $this->showCancelModal = false;
            $this->quotation->refresh();
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function duplicate(): void
    {
        try {
            $copy = app(QuotationWorkflowService::class)->duplicate($this->quotation, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.sales_workflow.quotation_duplicated'));
            $this->redirect(route('quotations.show', $copy), navigate: true);
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function convert(): void
    {
        try {
            $order = app(QuotationWorkflowService::class)->convertToSaleOrder($this->quotation, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.sales_workflow.quotation_converted'));
            $this->redirect(route('sale-orders.show', $order), navigate: true);
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }
}; ?>

<section class="scf-page">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl">{{ $quotation->reference_number }}</flux:heading>
                <flux:badge :color="$quotation->status->color()">{{ $quotation->status->label() }}</flux:badge>
            </div>
            <flux:subheading class="mt-1">
                {{ $quotation->contact?->name ?? __('No customer') }} ·
                {{ $quotation->quotation_date->format('d M Y') }}
            </flux:subheading>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('update', $quotation)
                @if ($quotation->status->isEditable())
                    <flux:button :href="route('quotations.edit', $quotation)" icon="pencil-square" variant="primary" wire:navigate>
                        {{ __('Edit') }}
                    </flux:button>
                @endif
                @if ($quotation->status->canTransitionTo(\App\Enums\QuotationStatus::Sent))
                    <flux:button wire:click="send" icon="paper-airplane">
                        {{ __('Send') }}
                    </flux:button>
                @endif
            @endcan

            @can('quotations.approve')
                @if ($quotation->status->canTransitionTo(\App\Enums\QuotationStatus::Accepted))
                    <flux:button wire:click="approve" icon="check-circle" variant="filled">
                        {{ __('Approve') }}
                    </flux:button>
                @endif
                @if ($quotation->status->canTransitionTo(\App\Enums\QuotationStatus::Rejected))
                    <flux:button wire:click="openRejectModal" icon="x-circle" variant="ghost">
                        {{ __('Reject') }}
                    </flux:button>
                @endif
            @endcan

            @can('quotations.convert')
                @if ($quotation->status === \App\Enums\QuotationStatus::Accepted && ! $quotation->converted_sale_order_id)
                    <flux:button wire:click="convert" icon="arrow-right-circle" variant="filled">
                        {{ __('Convert to order') }}
                    </flux:button>
                @endif
            @endcan

            @can('quotations.create')
                <flux:button wire:click="duplicate" icon="document-duplicate" variant="ghost">
                    {{ __('Duplicate') }}
                </flux:button>
            @endcan

            @can('update', $quotation)
                @if ($quotation->status->canTransitionTo(\App\Enums\QuotationStatus::Cancelled))
                    <flux:button wire:click="openCancelModal" icon="x-mark" variant="ghost">
                        {{ __('Cancel') }}
                    </flux:button>
                @endif
            @endcan

            @if (Route::has('print.quotation'))
                <x-print-button type="quotation" :id="$quotation->id" />
            @endif

            <flux:button :href="route('quotations.index')" variant="ghost" wire:navigate>{{ __('Back') }}</flux:button>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Details card --}}
        <div class="scf-card space-y-3 lg:col-span-1">
            <flux:heading size="lg">{{ __('Details') }}</flux:heading>
            <div class="space-y-2 text-sm">
                <p><span class="text-zinc-500">{{ __('Customer') }}:</span>
                    {{ $quotation->contact?->name ?? '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Salesperson') }}:</span>
                    {{ $quotation->salesperson?->name ?? '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Date') }}:</span>
                    {{ $quotation->quotation_date->format('d M Y') }}</p>
                <p><span class="text-zinc-500">{{ __('Valid until') }}:</span>
                    {{ $quotation->valid_until?->format('d M Y') ?? '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Currency') }}:</span>
                    {{ $quotation->currency_code }}</p>
            </div>

            @if ($quotation->notes)
                <div class="border-t border-zinc-100 pt-3 dark:border-zinc-800">
                    <p class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Notes') }}</p>
                    <p class="mt-1 text-sm">{{ $quotation->notes }}</p>
                </div>
            @endif

            @if ($quotation->terms)
                <div class="border-t border-zinc-100 pt-3 dark:border-zinc-800">
                    <p class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Terms') }}</p>
                    <p class="mt-1 text-sm">{{ $quotation->terms }}</p>
                </div>
            @endif
        </div>

        <div class="space-y-6 lg:col-span-2">
            {{-- Line items --}}
            <div class="scf-card">
                <flux:heading size="lg" class="mb-4">{{ __('Line items') }}</flux:heading>
                @if ($quotation->lines->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700 text-left">
                                    <th class="pb-2 pr-4 font-medium text-zinc-500">{{ __('Description') }}</th>
                                    <th class="pb-2 pr-4 font-medium text-zinc-500 text-right">{{ __('Qty') }}</th>
                                    <th class="pb-2 pr-4 font-medium text-zinc-500 text-right">{{ __('Unit price') }}</th>
                                    <th class="pb-2 pr-4 font-medium text-zinc-500 text-right">{{ __('Discount') }}</th>
                                    <th class="pb-2 font-medium text-zinc-500 text-right">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($quotation->lines as $line)
                                    <tr>
                                        <td class="py-2 pr-4">
                                            @if ($line->product)
                                                <p class="font-medium">{{ $line->product->name }}</p>
                                            @endif
                                            @if ($line->description)
                                                <p class="text-xs text-zinc-500">{{ $line->description }}</p>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-4 text-right">{{ $line->quantity }}</td>
                                        <td class="py-2 pr-4 text-right">{{ number_format((float) $line->unit_price, 2) }}</td>
                                        <td class="py-2 pr-4 text-right">{{ number_format((float) $line->discount_amount, 2) }}</td>
                                        <td class="py-2 text-right font-medium">{{ number_format((float) $line->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t border-zinc-200 dark:border-zinc-700">
                                <tr>
                                    <td colspan="4" class="pt-3 pr-4 text-right text-zinc-500">{{ __('Subtotal') }}</td>
                                    <td class="pt-3 text-right">{{ number_format((float) $quotation->subtotal_amount, 2) }}</td>
                                </tr>
                                @if ((float) $quotation->discount_amount > 0)
                                    <tr>
                                        <td colspan="4" class="pt-1 pr-4 text-right text-zinc-500">{{ __('Discount') }}</td>
                                        <td class="pt-1 text-right text-red-600">−{{ number_format((float) $quotation->discount_amount, 2) }}</td>
                                    </tr>
                                @endif
                                @if ((float) $quotation->tax_amount > 0)
                                    <tr>
                                        <td colspan="4" class="pt-1 pr-4 text-right text-zinc-500">{{ __('Tax') }}</td>
                                        <td class="pt-1 text-right">{{ number_format((float) $quotation->tax_amount, 2) }}</td>
                                    </tr>
                                @endif
                                <tr class="font-semibold">
                                    <td colspan="4" class="pt-2 pr-4 text-right">{{ __('Total') }}</td>
                                    <td class="pt-2 text-right">{{ $quotation->currency_code }} {{ number_format((float) $quotation->total_amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-zinc-500">{{ __('No line items.') }}</p>
                @endif
            </div>

            {{-- Related documents --}}
            <x-sales.related-documents
                :quotation="null"
                :sale-order="$quotation->convertedSaleOrder"
                :invoice="null"
                :payment="null"
            />

            <livewire:activity.activity-timeline :subject="$quotation" :key="'activity-quotation-'.$quotation->id" />
        </div>
    </div>

    {{-- Reject modal --}}
    <flux:modal wire:model="showRejectModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Reject quotation') }}</flux:heading>
            <flux:textarea wire:model="rejectReason" :label="__('Reason (optional)')" rows="3" />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="reject">{{ __('Reject') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Cancel modal --}}
    <flux:modal wire:model="showCancelModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Cancel quotation') }}</flux:heading>
            <flux:textarea wire:model="cancelReason" :label="__('Reason (optional)')" rows="3" />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Back') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="cancel">{{ __('Cancel quotation') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
