<?php

use App\Models\PurchaseRequest;
use App\Services\Purchasing\PurchaseRequestWorkflowService;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Purchase request')] class extends Component {
    public PurchaseRequest $purchaseRequest;

    public string $rejectReason = '';
    public bool $showRejectModal = false;
    public string $cancelReason = '';
    public bool $showCancelModal = false;

    public function mount(PurchaseRequest $purchaseRequest): void
    {
        $this->authorize('view', $purchaseRequest);
        $this->purchaseRequest = $purchaseRequest->load([
            'lines.product',
            'requester',
            'convertedRfq',
            'events.user',
        ]);
    }

    public function submit(): void
    {
        try {
            app(PurchaseRequestWorkflowService::class)->submit($this->purchaseRequest, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.pr_submitted'));
            $this->purchaseRequest->refresh();
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function approve(): void
    {
        try {
            app(PurchaseRequestWorkflowService::class)->approve($this->purchaseRequest, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.pr_approved'));
            $this->purchaseRequest->refresh();
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
            app(PurchaseRequestWorkflowService::class)->reject($this->purchaseRequest, auth()->user(), $this->rejectReason ?: null);
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.pr_rejected'));
            $this->showRejectModal = false;
            $this->purchaseRequest->refresh();
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
            app(PurchaseRequestWorkflowService::class)->cancel($this->purchaseRequest, auth()->user(), $this->cancelReason ?: null);
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.pr_cancelled'));
            $this->showCancelModal = false;
            $this->purchaseRequest->refresh();
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function duplicate(): void
    {
        try {
            $copy = app(PurchaseRequestWorkflowService::class)->duplicate($this->purchaseRequest, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.pr_duplicated'));
            $this->redirect(route('purchase-requests.show', $copy), navigate: true);
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function convertToRfq(): void
    {
        try {
            $rfq = app(PurchaseRequestWorkflowService::class)->convertToRfq($this->purchaseRequest, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.pr_converted'));
            $this->redirect(route('rfqs.show', $rfq), navigate: true);
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }
}; ?>

<section class="scf-page">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl">{{ $purchaseRequest->reference_number }}</flux:heading>
                <flux:badge :color="$purchaseRequest->status->color()">{{ $purchaseRequest->status->label() }}</flux:badge>
            </div>
            <flux:subheading class="mt-1">
                {{ $purchaseRequest->requester?->name ?? auth()->user()->name }} ·
                {{ $purchaseRequest->request_date->format('d M Y') }}
            </flux:subheading>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('update', $purchaseRequest)
                @if ($purchaseRequest->status->isEditable())
                    <flux:button :href="route('purchase-requests.edit', $purchaseRequest)" icon="pencil-square" variant="primary" wire:navigate>
                        {{ __('Edit') }}
                    </flux:button>
                @endif
                @if ($purchaseRequest->status->canTransitionTo(\App\Enums\PurchaseRequestStatus::Submitted))
                    <flux:button wire:click="submit" icon="paper-airplane">{{ __('Submit') }}</flux:button>
                @endif
            @endcan

            @can('purchase-requests.approve')
                @if ($purchaseRequest->status === \App\Enums\PurchaseRequestStatus::Submitted)
                    <flux:button wire:click="approve" icon="check-circle" variant="filled">{{ __('Approve') }}</flux:button>
                    <flux:button wire:click="openRejectModal" icon="x-circle" variant="ghost">{{ __('Reject') }}</flux:button>
                @endif
            @endcan

            @can('purchase-requests.convert')
                @if ($purchaseRequest->status === \App\Enums\PurchaseRequestStatus::Approved && ! $purchaseRequest->converted_rfq_id)
                    <flux:button wire:click="convertToRfq" icon="arrow-right-circle" variant="filled">
                        {{ __('Convert to RFQ') }}
                    </flux:button>
                @endif
            @endcan

            @can('purchase-requests.create')
                <flux:button wire:click="duplicate" icon="document-duplicate" variant="ghost">{{ __('Duplicate') }}</flux:button>
            @endcan

            @can('update', $purchaseRequest)
                @if ($purchaseRequest->status->canTransitionTo(\App\Enums\PurchaseRequestStatus::Cancelled))
                    <flux:button wire:click="openCancelModal" icon="x-mark" variant="ghost">{{ __('Cancel') }}</flux:button>
                @endif
            @endcan

            <flux:button :href="route('purchase-requests.index')" variant="ghost" wire:navigate>{{ __('Back') }}</flux:button>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="scf-card space-y-3 lg:col-span-1">
            <flux:heading size="lg">{{ __('Details') }}</flux:heading>
            <div class="space-y-2 text-sm">
                <p><span class="text-zinc-500">{{ __('Requester') }}:</span> {{ $purchaseRequest->requester?->name ?? auth()->user()->name }}</p>
                <p><span class="text-zinc-500">{{ __('Department') }}:</span> {{ $purchaseRequest->department ?? '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Request date') }}:</span> {{ $purchaseRequest->request_date->format('d M Y') }}</p>
                <p><span class="text-zinc-500">{{ __('Needed by') }}:</span> {{ $purchaseRequest->needed_by?->format('d M Y') ?? '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Currency') }}:</span> {{ $purchaseRequest->currency_code }}</p>
            </div>
            @if ($purchaseRequest->notes)
                <div class="border-t border-zinc-100 pt-3 dark:border-zinc-800">
                    <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ __('Notes') }}</p>
                    <p class="mt-1 text-sm">{{ $purchaseRequest->notes }}</p>
                </div>
            @endif
        </div>

        <div class="space-y-6 lg:col-span-2">
            <div class="scf-card">
                <flux:heading size="lg" class="mb-4">{{ __('Line items') }}</flux:heading>
                @if ($purchaseRequest->lines->isNotEmpty())
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
                                @foreach ($purchaseRequest->lines as $line)
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
                                <tr class="font-semibold">
                                    <td colspan="4" class="pt-3 pr-4 text-right">{{ __('Total') }}</td>
                                    <td class="pt-3 text-right">{{ $purchaseRequest->currency_code }} {{ number_format((float) $purchaseRequest->total_amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-zinc-500">{{ __('No line items.') }}</p>
                @endif
            </div>

            <x-purchasing.related-documents
                :purchase-request="null"
                :rfq="$purchaseRequest->convertedRfq"
                :purchase-order="null"
                :bill="null"
                :payment="null"
            />

            <livewire:activity.activity-timeline :subject="$purchaseRequest" :key="'activity-purchaseRequest-'.$purchaseRequest->id" />
        </div>
    </div>

    <flux:modal wire:model="showRejectModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Reject purchase request') }}</flux:heading>
            <flux:textarea wire:model="rejectReason" :label="__('Reason (optional)')" rows="3" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button variant="danger" wire:click="reject">{{ __('Reject') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="showCancelModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Cancel purchase request') }}</flux:heading>
            <flux:textarea wire:model="cancelReason" :label="__('Reason (optional)')" rows="3" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Back') }}</flux:button></flux:modal.close>
                <flux:button variant="danger" wire:click="cancel">{{ __('Cancel request') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
