<?php

use App\Models\AccountingPosting;
use App\Models\SaleOrder;
use App\Services\Sales\SaleOrderWorkflowService;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Sale order')] class extends Component {
    public SaleOrder $saleOrder;

    public string $rejectReason = '';
    public bool $showRejectModal = false;
    public bool $showCancelModal = false;
    public string $cancelReason = '';

    public function mount(SaleOrder $saleOrder): void
    {
        $this->authorize('view', $saleOrder);
        $this->saleOrder = $saleOrder->load([
            'contact',
            'lines.product',
            'quotation',
            'invoices',
            'branch',
            'warehouse',
            'salesperson',
            'events.user',
        ]);
    }

    public function submit(): void
    {
        try {
            app(SaleOrderWorkflowService::class)->submit($this->saleOrder, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.sales_workflow.order_submitted'));
            $this->saleOrder->refresh();
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function approve(): void
    {
        try {
            app(SaleOrderWorkflowService::class)->approve($this->saleOrder, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.sales_workflow.order_approved'));
            $this->saleOrder->refresh();
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function openRejectModal(): void
    {
        $this->rejectReason = '';
        $this->showRejectModal = true;
    }

    public function rejectToDraft(): void
    {
        $this->validate(['rejectReason' => 'nullable|string|max:500']);
        try {
            app(SaleOrderWorkflowService::class)->rejectToDraft($this->saleOrder, auth()->user(), $this->rejectReason ?: null);
            Flux::toast(variant: 'success', text: __('scf.sales_workflow.order_rejected'));
            $this->showRejectModal = false;
            $this->saleOrder->refresh();
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function confirm(): void
    {
        try {
            app(SaleOrderWorkflowService::class)->confirm($this->saleOrder, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.sales_workflow.order_confirmed'));
            $this->saleOrder->refresh();
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
            app(SaleOrderWorkflowService::class)->cancel($this->saleOrder, auth()->user(), $this->cancelReason ?: null);
            Flux::toast(variant: 'success', text: __('scf.sales_workflow.order_cancelled'));
            $this->showCancelModal = false;
            $this->saleOrder->refresh();
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function duplicate(): void
    {
        try {
            $copy = app(SaleOrderWorkflowService::class)->duplicate($this->saleOrder, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.sales_workflow.order_duplicated'));
            $this->redirect(route('sale-orders.show', $copy), navigate: true);
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function createInvoice(): void
    {
        try {
            $invoice = app(SaleOrderWorkflowService::class)->createInvoice($this->saleOrder, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.sales_workflow.invoice_created'));
            $this->redirect(route('invoices.show', $invoice), navigate: true);
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }
}; ?>

<section class="scf-page">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl">{{ $saleOrder->reference_number }}</flux:heading>
                <flux:badge :color="$saleOrder->status->color()">{{ $saleOrder->status->label() }}</flux:badge>
            </div>
            <flux:subheading class="mt-1">
                {{ $saleOrder->contact?->name ?? __('No customer') }} ·
                {{ $saleOrder->order_date->format('d M Y') }}
            </flux:subheading>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('update', $saleOrder)
                @if ($saleOrder->status->isEditable())
                    <flux:button :href="route('sale-orders.edit', $saleOrder)" icon="pencil-square" variant="primary" wire:navigate>
                        {{ __('Edit') }}
                    </flux:button>
                @endif
                @if ($saleOrder->status->canTransitionTo(\App\Enums\SaleOrderStatus::PendingApproval))
                    <flux:button wire:click="submit" icon="paper-airplane">
                        {{ __('Submit') }}
                    </flux:button>
                @endif
            @endcan

            @can('sale-orders.approve')
                @if ($saleOrder->status === \App\Enums\SaleOrderStatus::PendingApproval)
                    <flux:button wire:click="approve" icon="check-circle" variant="filled">
                        {{ __('Approve') }}
                    </flux:button>
                    <flux:button wire:click="openRejectModal" icon="x-circle" variant="ghost">
                        {{ __('Reject') }}
                    </flux:button>
                @endif
                @if (in_array($saleOrder->status, [\App\Enums\SaleOrderStatus::Approved, \App\Enums\SaleOrderStatus::Draft, \App\Enums\SaleOrderStatus::PendingApproval], true))
                    <flux:button wire:click="confirm" icon="check-badge" variant="filled">
                        {{ __('Confirm') }}
                    </flux:button>
                @endif
            @endcan

            @if ($saleOrder->status->canInvoice())
                @can('invoices.create')
                    <flux:button wire:click="createInvoice" icon="document-plus">
                        {{ __('Create invoice') }}
                    </flux:button>
                @endcan
            @endif

            @can('sale-orders.create')
                <flux:button wire:click="duplicate" icon="document-duplicate" variant="ghost">
                    {{ __('Duplicate') }}
                </flux:button>
            @endcan

            @can('update', $saleOrder)
                @if ($saleOrder->status->canTransitionTo(\App\Enums\SaleOrderStatus::Cancelled))
                    <flux:button wire:click="openCancelModal" icon="x-mark" variant="ghost">
                        {{ __('Cancel') }}
                    </flux:button>
                @endif
            @endcan

            @if (Route::has('print.sale-order'))
                <x-print-button type="sale-order" :id="$saleOrder->id" />
            @endif

            <flux:button :href="route('sale-orders.index')" variant="ghost" wire:navigate>{{ __('Back') }}</flux:button>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Details --}}
        <div class="scf-card space-y-3 lg:col-span-1">
            <flux:heading size="lg">{{ __('Details') }}</flux:heading>
            <div class="space-y-2 text-sm">
                <p><span class="text-zinc-500">{{ __('Customer') }}:</span> {{ $saleOrder->contact?->name ?? '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Salesperson') }}:</span> {{ $saleOrder->salesperson?->name ?? '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Warehouse') }}:</span> {{ $saleOrder->warehouse?->name ?? '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Branch') }}:</span> {{ $saleOrder->branch?->name ?? '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Order date') }}:</span> {{ $saleOrder->order_date->format('d M Y') }}</p>
                <p><span class="text-zinc-500">{{ __('Delivery date') }}:</span> {{ $saleOrder->delivery_date?->format('d M Y') ?? '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Currency') }}:</span> {{ $saleOrder->currency_code }}</p>
            </div>
            @if ($saleOrder->billing_address)
                <div class="border-t border-zinc-100 pt-3 dark:border-zinc-800">
                    <p class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Billing address') }}</p>
                    <p class="mt-1 text-sm whitespace-pre-wrap">{{ $saleOrder->billing_address }}</p>
                </div>
            @endif
            @if ($saleOrder->shipping_address)
                <div class="border-t border-zinc-100 pt-3 dark:border-zinc-800">
                    <p class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Shipping address') }}</p>
                    <p class="mt-1 text-sm whitespace-pre-wrap">{{ $saleOrder->shipping_address }}</p>
                </div>
            @endif
            @if ($saleOrder->notes)
                <div class="border-t border-zinc-100 pt-3 dark:border-zinc-800">
                    <p class="text-xs font-medium text-zinc-500 uppercase tracking-wide">{{ __('Notes') }}</p>
                    <p class="mt-1 text-sm">{{ $saleOrder->notes }}</p>
                </div>
            @endif
        </div>

        <div class="space-y-6 lg:col-span-2">
            {{-- Line items with invoicing progress --}}
            <div class="scf-card">
                <flux:heading size="lg" class="mb-4">{{ __('Line items') }}</flux:heading>
                @if ($saleOrder->lines->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700 text-left">
                                    <th class="pb-2 pr-3 font-medium text-zinc-500">{{ __('Description') }}</th>
                                    <th class="pb-2 pr-3 font-medium text-zinc-500 text-right">{{ __('Qty') }}</th>
                                    <th class="pb-2 pr-3 font-medium text-zinc-500 text-right">{{ __('Invoiced') }}</th>
                                    <th class="pb-2 pr-3 font-medium text-zinc-500 text-right">{{ __('Remaining') }}</th>
                                    <th class="pb-2 pr-3 font-medium text-zinc-500 text-right">{{ __('Unit price') }}</th>
                                    <th class="pb-2 font-medium text-zinc-500 text-right">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($saleOrder->lines as $line)
                                    <tr>
                                        <td class="py-2 pr-3">
                                            @if ($line->product)
                                                <p class="font-medium">{{ $line->product->name }}</p>
                                            @endif
                                            @if ($line->description)
                                                <p class="text-xs text-zinc-500">{{ $line->description }}</p>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-3 text-right">{{ $line->quantity }}</td>
                                        <td class="py-2 pr-3 text-right text-blue-600">{{ $line->quantity_invoiced }}</td>
                                        <td class="py-2 pr-3 text-right text-amber-600">
                                            {{ max(0, (float) $line->quantity - (float) $line->quantity_invoiced) }}
                                        </td>
                                        <td class="py-2 pr-3 text-right">{{ number_format((float) $line->unit_price, 2) }}</td>
                                        <td class="py-2 text-right font-medium">{{ number_format((float) $line->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t border-zinc-200 dark:border-zinc-700">
                                <tr>
                                    <td colspan="5" class="pt-3 pr-3 text-right text-zinc-500">{{ __('Subtotal') }}</td>
                                    <td class="pt-3 text-right">{{ number_format((float) $saleOrder->subtotal_amount, 2) }}</td>
                                </tr>
                                @if ((float) $saleOrder->discount_amount > 0)
                                    <tr>
                                        <td colspan="5" class="pt-1 pr-3 text-right text-zinc-500">{{ __('Discount') }}</td>
                                        <td class="pt-1 text-right text-red-600">−{{ number_format((float) $saleOrder->discount_amount, 2) }}</td>
                                    </tr>
                                @endif
                                @if ((float) $saleOrder->tax_amount > 0)
                                    <tr>
                                        <td colspan="5" class="pt-1 pr-3 text-right text-zinc-500">{{ __('Tax') }}</td>
                                        <td class="pt-1 text-right">{{ number_format((float) $saleOrder->tax_amount, 2) }}</td>
                                    </tr>
                                @endif
                                <tr class="font-semibold">
                                    <td colspan="5" class="pt-2 pr-3 text-right">{{ __('Total') }}</td>
                                    <td class="pt-2 text-right">{{ $saleOrder->currency_code }} {{ number_format((float) $saleOrder->total_amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-zinc-500">{{ __('No line items.') }}</p>
                @endif
            </div>

            {{-- Invoices list --}}
            @if ($saleOrder->invoices->isNotEmpty())
                <div class="scf-card">
                    <flux:heading size="lg" class="mb-4">{{ __('Invoices') }}</flux:heading>
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($saleOrder->invoices as $inv)
                            <div class="flex items-center justify-between py-3">
                                <div class="text-sm">
                                    @can('view', $inv)
                                        <a href="{{ route('invoices.show', $inv) }}" wire:navigate class="font-medium hover:underline">
                                            {{ $inv->reference_number }}
                                        </a>
                                    @else
                                        <span class="font-medium">{{ $inv->reference_number }}</span>
                                    @endcan
                                    <p class="text-xs text-zinc-500">{{ $inv->invoice_date->format('d M Y') }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <flux:badge size="sm" :color="$inv->status->color()">{{ $inv->status->label() }}</flux:badge>
                                    <span class="text-sm font-medium">{{ number_format((float) $inv->total_amount, 2) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Related documents --}}
            <x-sales.related-documents
                :quotation="$saleOrder->quotation"
                :sale-order="null"
                :invoice="$saleOrder->invoices->first()"
                :payment="null"
            />

            <livewire:activity.activity-timeline :subject="$saleOrder" :key="'activity-saleOrder-'.$saleOrder->id" />
        </div>
    </div>

    {{-- Reject modal --}}
    <flux:modal wire:model="showRejectModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Reject to draft') }}</flux:heading>
            <flux:textarea wire:model="rejectReason" :label="__('Reason (optional)')" rows="3" />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="rejectToDraft">{{ __('Reject') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Cancel modal --}}
    <flux:modal wire:model="showCancelModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Cancel order') }}</flux:heading>
            <flux:textarea wire:model="cancelReason" :label="__('Reason (optional)')" rows="3" />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Back') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="cancel">{{ __('Cancel order') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
