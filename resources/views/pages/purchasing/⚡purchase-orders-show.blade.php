<?php

use App\Models\PurchaseOrder;
use App\Services\Purchasing\PurchaseOrderWorkflowService;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Purchase order')] class extends Component {
    public PurchaseOrder $purchaseOrder;

    public string $rejectReason = '';
    public bool $showRejectModal = false;
    public string $cancelReason = '';
    public bool $showCancelModal = false;

    public function mount(PurchaseOrder $purchaseOrder): void
    {
        $this->authorize('view', $purchaseOrder);
        $this->purchaseOrder = $purchaseOrder->load([
            'contact',
            'lines.product',
            'rfq',
            'purchaseRequest',
            'bills',
            'warehouse',
            'branch',
            'buyer',
            'events.user',
        ]);
    }

    public function submit(): void
    {
        try {
            app(PurchaseOrderWorkflowService::class)->submit($this->purchaseOrder, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.order_submitted'));
            $this->purchaseOrder->refresh();
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function approve(): void
    {
        try {
            app(PurchaseOrderWorkflowService::class)->approve($this->purchaseOrder, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.order_approved'));
            $this->purchaseOrder->refresh();
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
            app(PurchaseOrderWorkflowService::class)->rejectToDraft($this->purchaseOrder, auth()->user(), $this->rejectReason ?: null);
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.order_rejected'));
            $this->showRejectModal = false;
            $this->purchaseOrder->refresh();
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function confirm(): void
    {
        try {
            app(PurchaseOrderWorkflowService::class)->confirm($this->purchaseOrder, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.order_confirmed'));
            $this->purchaseOrder->refresh();
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
            app(PurchaseOrderWorkflowService::class)->cancel($this->purchaseOrder, auth()->user(), $this->cancelReason ?: null);
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.order_cancelled'));
            $this->showCancelModal = false;
            $this->purchaseOrder->refresh();
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function duplicate(): void
    {
        try {
            $copy = app(PurchaseOrderWorkflowService::class)->duplicate($this->purchaseOrder, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.order_duplicated'));
            $this->redirect(route('purchase-orders.show', $copy), navigate: true);
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function createBill(): void
    {
        try {
            $bill = app(PurchaseOrderWorkflowService::class)->createBill($this->purchaseOrder, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.bill_created'));
            $this->redirect(route('bills.show', $bill), navigate: true);
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }
}; ?>

<section class="scf-page">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl">{{ $purchaseOrder->reference_number }}</flux:heading>
                <flux:badge :color="$purchaseOrder->status->color()">{{ $purchaseOrder->status->label() }}</flux:badge>
            </div>
            <flux:subheading class="mt-1">
                {{ $purchaseOrder->contact?->name ?? __('No vendor') }} ·
                {{ $purchaseOrder->order_date->format('d M Y') }}
            </flux:subheading>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('update', $purchaseOrder)
                @if ($purchaseOrder->status->isEditable())
                    <flux:button :href="route('purchase-orders.edit', $purchaseOrder)" icon="pencil-square" variant="primary" wire:navigate>
                        {{ __('Edit') }}
                    </flux:button>
                @endif
                @if ($purchaseOrder->status->canTransitionTo(\App\Enums\PurchaseOrderStatus::PendingApproval))
                    <flux:button wire:click="submit" icon="paper-airplane">{{ __('Submit') }}</flux:button>
                @endif
            @endcan

            @can('purchase-orders.approve')
                @if ($purchaseOrder->status === \App\Enums\PurchaseOrderStatus::PendingApproval)
                    <flux:button wire:click="approve" icon="check-circle" variant="filled">{{ __('Approve') }}</flux:button>
                    <flux:button wire:click="openRejectModal" icon="x-circle" variant="ghost">{{ __('Reject') }}</flux:button>
                @endif
                @if (in_array($purchaseOrder->status, [\App\Enums\PurchaseOrderStatus::Approved, \App\Enums\PurchaseOrderStatus::Draft, \App\Enums\PurchaseOrderStatus::PendingApproval], true))
                    <flux:button wire:click="confirm" icon="check-badge" variant="filled">{{ __('Confirm') }}</flux:button>
                @endif
            @endcan

            @if ($purchaseOrder->status->canBill())
                @can('bills.create')
                    <flux:button wire:click="createBill" icon="document-plus">{{ __('Create bill') }}</flux:button>
                @endcan
            @endif

            @can('purchase-orders.create')
                <flux:button wire:click="duplicate" icon="document-duplicate" variant="ghost">{{ __('Duplicate') }}</flux:button>
            @endcan

            @can('update', $purchaseOrder)
                @if ($purchaseOrder->status->canTransitionTo(\App\Enums\PurchaseOrderStatus::Cancelled))
                    <flux:button wire:click="openCancelModal" icon="x-mark" variant="ghost">{{ __('Cancel') }}</flux:button>
                @endif
            @endcan

            @if (Route::has('print.purchase-order'))
                <x-print-button type="purchase-order" :id="$purchaseOrder->id" />
            @endif

            <flux:button :href="route('purchase-orders.index')" variant="ghost" wire:navigate>{{ __('Back') }}</flux:button>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="scf-card space-y-3 lg:col-span-1">
            <flux:heading size="lg">{{ __('Details') }}</flux:heading>
            <div class="space-y-2 text-sm">
                <p><span class="text-zinc-500">{{ __('Vendor') }}:</span> {{ $purchaseOrder->contact?->name ?? '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Buyer') }}:</span> {{ $purchaseOrder->buyer?->name ?? '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Warehouse') }}:</span> {{ $purchaseOrder->warehouse?->name ?? '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Branch') }}:</span> {{ $purchaseOrder->branch?->name ?? '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Order date') }}:</span> {{ $purchaseOrder->order_date->format('d M Y') }}</p>
                <p><span class="text-zinc-500">{{ __('Expected date') }}:</span> {{ $purchaseOrder->expected_date?->format('d M Y') ?? '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Currency') }}:</span> {{ $purchaseOrder->currency_code }}</p>
            </div>
            @if ($purchaseOrder->notes)
                <div class="border-t border-zinc-100 pt-3 dark:border-zinc-800">
                    <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ __('Notes') }}</p>
                    <p class="mt-1 text-sm">{{ $purchaseOrder->notes }}</p>
                </div>
            @endif
        </div>

        <div class="space-y-6 lg:col-span-2">
            {{-- Line items with billed progress --}}
            <div class="scf-card">
                <flux:heading size="lg" class="mb-4">{{ __('Line items') }}</flux:heading>
                @if ($purchaseOrder->lines->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700 text-left">
                                    <th class="pb-2 pr-3 font-medium text-zinc-500">{{ __('Description') }}</th>
                                    <th class="pb-2 pr-3 font-medium text-zinc-500 text-right">{{ __('Qty') }}</th>
                                    <th class="pb-2 pr-3 font-medium text-zinc-500 text-right">{{ __('Billed') }}</th>
                                    <th class="pb-2 pr-3 font-medium text-zinc-500 text-right">{{ __('Remaining') }}</th>
                                    <th class="pb-2 pr-3 font-medium text-zinc-500 text-right">{{ __('Unit price') }}</th>
                                    <th class="pb-2 font-medium text-zinc-500 text-right">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($purchaseOrder->lines as $line)
                                    <tr>
                                        <td class="py-2 pr-3">
                                            @if ($line->product)<p class="font-medium">{{ $line->product->name }}</p>@endif
                                            @if ($line->description)<p class="text-xs text-zinc-500">{{ $line->description }}</p>@endif
                                        </td>
                                        <td class="py-2 pr-3 text-right">{{ $line->quantity }}</td>
                                        <td class="py-2 pr-3 text-right text-blue-600">{{ $line->quantity_billed }}</td>
                                        <td class="py-2 pr-3 text-right text-amber-600">
                                            {{ max(0, (float) $line->quantity - (float) $line->quantity_billed) }}
                                        </td>
                                        <td class="py-2 pr-3 text-right">{{ number_format((float) $line->unit_price, 2) }}</td>
                                        <td class="py-2 text-right font-medium">{{ number_format((float) $line->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t border-zinc-200 dark:border-zinc-700">
                                <tr>
                                    <td colspan="5" class="pt-3 pr-3 text-right text-zinc-500">{{ __('Subtotal') }}</td>
                                    <td class="pt-3 text-right">{{ number_format((float) $purchaseOrder->subtotal_amount, 2) }}</td>
                                </tr>
                                @if ((float) $purchaseOrder->tax_amount > 0)
                                    <tr>
                                        <td colspan="5" class="pt-1 pr-3 text-right text-zinc-500">{{ __('Tax') }}</td>
                                        <td class="pt-1 text-right">{{ number_format((float) $purchaseOrder->tax_amount, 2) }}</td>
                                    </tr>
                                @endif
                                <tr class="font-semibold">
                                    <td colspan="5" class="pt-2 pr-3 text-right">{{ __('Total') }}</td>
                                    <td class="pt-2 text-right">{{ $purchaseOrder->currency_code }} {{ number_format((float) $purchaseOrder->total_amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-zinc-500">{{ __('No line items.') }}</p>
                @endif
            </div>

            {{-- Bills list --}}
            @if ($purchaseOrder->bills->isNotEmpty())
                <div class="scf-card">
                    <flux:heading size="lg" class="mb-4">{{ __('Bills') }}</flux:heading>
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @foreach ($purchaseOrder->bills as $bill)
                            <div class="flex items-center justify-between py-3">
                                <div class="text-sm">
                                    @can('view', $bill)
                                        @if (Route::has('bills.show'))
                                            <a href="{{ route('bills.show', $bill) }}" wire:navigate class="font-medium hover:underline">
                                                {{ $bill->reference_number }}
                                            </a>
                                        @else
                                            <span class="font-medium">{{ $bill->reference_number }}</span>
                                        @endif
                                    @else
                                        <span class="font-medium">{{ $bill->reference_number }}</span>
                                    @endcan
                                    <p class="text-xs text-zinc-500">{{ $bill->bill_date->format('d M Y') }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <flux:badge size="sm" :color="$bill->status->color()">{{ $bill->status->label() }}</flux:badge>
                                    <span class="text-sm font-medium">{{ number_format((float) $bill->total_amount, 2) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <x-purchasing.related-documents
                :purchase-request="$purchaseOrder->purchaseRequest"
                :rfq="$purchaseOrder->rfq"
                :purchase-order="null"
                :bill="$purchaseOrder->bills->first()"
                :payment="null"
            />

            <livewire:activity.activity-timeline :subject="$purchaseOrder" :key="'activity-purchaseOrder-'.$purchaseOrder->id" />
        </div>
    </div>

    <flux:modal wire:model="showRejectModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Reject to draft') }}</flux:heading>
            <flux:textarea wire:model="rejectReason" :label="__('Reason (optional)')" rows="3" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button variant="danger" wire:click="rejectToDraft">{{ __('Reject') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showCancelModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Cancel order') }}</flux:heading>
            <flux:textarea wire:model="cancelReason" :label="__('Reason (optional)')" rows="3" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Back') }}</flux:button></flux:modal.close>
                <flux:button variant="danger" wire:click="cancel">{{ __('Cancel order') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
