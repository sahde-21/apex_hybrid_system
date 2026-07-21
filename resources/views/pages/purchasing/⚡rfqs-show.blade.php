<?php

use App\Models\Rfq;
use App\Services\Purchasing\RfqWorkflowService;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('RFQ')] class extends Component {
    public Rfq $rfq;

    public string $rejectReason = '';
    public bool $showRejectModal = false;
    public string $cancelReason = '';
    public bool $showCancelModal = false;

    // Vendor response modal
    public bool $showVendorResponseModal = false;
    public ?int $respondingVendorId = null;
    public string $vendorQuotedTotal = '';
    public string $vendorQuotedTax = '';
    public string $vendorNotes = '';

    // Accept modal
    public bool $showAcceptModal = false;
    public ?int $acceptVendorId = null;

    public function mount(Rfq $rfq): void
    {
        $this->authorize('view', $rfq);
        $this->rfq = $rfq->load([
            'lines.product',
            'vendors.contact',
            'purchaseRequest',
            'convertedPurchaseOrder',
            'selectedVendor',
            'events.user',
        ]);

        app(RfqWorkflowService::class)->expireIfNeeded($rfq, auth()->user());
        $this->rfq->refresh();
    }

    public function send(): void
    {
        try {
            app(RfqWorkflowService::class)->send($this->rfq, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.rfq_sent'));
            $this->rfq->refresh()->load('vendors.contact');
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function openVendorResponseModal(int $contactId): void
    {
        $vendor = $this->rfq->vendors->firstWhere('contact_id', $contactId);
        $this->respondingVendorId = $contactId;
        $this->vendorQuotedTotal = $vendor ? (string) ($vendor->quoted_total ?? '') : '';
        $this->vendorQuotedTax = $vendor ? (string) ($vendor->quoted_tax ?? '') : '';
        $this->vendorNotes = $vendor ? ($vendor->notes ?? '') : '';
        $this->showVendorResponseModal = true;
    }

    public function recordVendorResponse(): void
    {
        $this->validate([
            'vendorQuotedTotal' => 'required|numeric|min:0',
            'vendorQuotedTax' => 'nullable|numeric|min:0',
            'vendorNotes' => 'nullable|string|max:1000',
        ]);

        try {
            app(RfqWorkflowService::class)->recordVendorResponse(
                $this->rfq,
                auth()->user(),
                $this->respondingVendorId,
                [
                    'quoted_total' => $this->vendorQuotedTotal,
                    'quoted_tax' => $this->vendorQuotedTax ?: null,
                    'notes' => $this->vendorNotes ?: null,
                ]
            );
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.vendor_response_recorded'));
            $this->showVendorResponseModal = false;
            $this->rfq->refresh()->load('vendors.contact');
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function openAcceptModal(int $contactId): void
    {
        $this->acceptVendorId = $contactId;
        $this->showAcceptModal = true;
    }

    public function accept(): void
    {
        try {
            app(RfqWorkflowService::class)->accept($this->rfq, auth()->user(), $this->acceptVendorId);
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.rfq_accepted'));
            $this->showAcceptModal = false;
            $this->rfq->refresh()->load('vendors.contact', 'selectedVendor');
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
            app(RfqWorkflowService::class)->reject($this->rfq, auth()->user(), $this->rejectReason ?: null);
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.rfq_rejected'));
            $this->showRejectModal = false;
            $this->rfq->refresh();
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
            app(RfqWorkflowService::class)->cancel($this->rfq, auth()->user(), $this->cancelReason ?: null);
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.rfq_cancelled'));
            $this->showCancelModal = false;
            $this->rfq->refresh();
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function duplicate(): void
    {
        try {
            $copy = app(RfqWorkflowService::class)->duplicate($this->rfq, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.rfq_duplicated'));
            $this->redirect(route('rfqs.show', $copy), navigate: true);
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }

    public function convertToPurchaseOrder(): void
    {
        try {
            $order = app(RfqWorkflowService::class)->convertToPurchaseOrder($this->rfq, auth()->user());
            Flux::toast(variant: 'success', text: __('scf.purchase_workflow.rfq_converted'));
            $this->redirect(route('purchase-orders.show', $order), navigate: true);
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }
}; ?>

<section class="scf-page">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl">{{ $rfq->reference_number }}</flux:heading>
                <flux:badge :color="$rfq->status->color()">{{ $rfq->status->label() }}</flux:badge>
            </div>
            <flux:subheading class="mt-1">
                {{ $rfq->rfq_date->format('d M Y') }}
                @if ($rfq->valid_until)
                    · {{ __('Valid until') }}: {{ $rfq->valid_until->format('d M Y') }}
                @endif
            </flux:subheading>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('update', $rfq)
                @if ($rfq->status->isEditable())
                    <flux:button :href="route('rfqs.edit', $rfq)" icon="pencil-square" variant="primary" wire:navigate>
                        {{ __('Edit') }}
                    </flux:button>
                @endif
                @if ($rfq->status->canTransitionTo(\App\Enums\RfqStatus::Sent))
                    <flux:button wire:click="send" icon="paper-airplane">{{ __('Send') }}</flux:button>
                @endif
            @endcan

            @can('rfqs.approve')
                @if (in_array($rfq->status, [\App\Enums\RfqStatus::VendorResponse, \App\Enums\RfqStatus::Sent], true))
                    <flux:button wire:click="openRejectModal" icon="x-circle" variant="ghost">{{ __('Reject') }}</flux:button>
                @endif
            @endcan

            @can('rfqs.convert')
                @if ($rfq->status === \App\Enums\RfqStatus::Accepted && ! $rfq->converted_purchase_order_id)
                    <flux:button wire:click="convertToPurchaseOrder" icon="arrow-right-circle" variant="filled">
                        {{ __('Convert to PO') }}
                    </flux:button>
                @endif
            @endcan

            @can('rfqs.create')
                <flux:button wire:click="duplicate" icon="document-duplicate" variant="ghost">{{ __('Duplicate') }}</flux:button>
            @endcan

            @can('update', $rfq)
                @if ($rfq->status->canTransitionTo(\App\Enums\RfqStatus::Cancelled))
                    <flux:button wire:click="openCancelModal" icon="x-mark" variant="ghost">{{ __('Cancel') }}</flux:button>
                @endif
            @endcan

            <flux:button :href="route('rfqs.index')" variant="ghost" wire:navigate>{{ __('Back') }}</flux:button>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="scf-card space-y-3 lg:col-span-1">
            <flux:heading size="lg">{{ __('Details') }}</flux:heading>
            <div class="space-y-2 text-sm">
                <p><span class="text-zinc-500">{{ __('RFQ date') }}:</span> {{ $rfq->rfq_date->format('d M Y') }}</p>
                <p><span class="text-zinc-500">{{ __('Valid until') }}:</span> {{ $rfq->valid_until?->format('d M Y') ?? '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Currency') }}:</span> {{ $rfq->currency_code }}</p>
                @if ($rfq->selectedVendor)
                    <p><span class="text-zinc-500">{{ __('Selected vendor') }}:</span>
                        <span class="font-medium text-green-700 dark:text-green-400">{{ $rfq->selectedVendor->name }}</span>
                    </p>
                @endif
                @if ($rfq->purchaseRequest)
                    <p><span class="text-zinc-500">{{ __('From PR') }}:</span>
                        @if (Route::has('purchase-requests.show'))
                            <a href="{{ route('purchase-requests.show', $rfq->purchaseRequest) }}" wire:navigate class="text-blue-600 hover:underline">
                                {{ $rfq->purchaseRequest->reference_number }}
                            </a>
                        @else
                            {{ $rfq->purchaseRequest->reference_number }}
                        @endif
                    </p>
                @endif
            </div>
            @if ($rfq->notes)
                <div class="border-t border-zinc-100 pt-3 dark:border-zinc-800">
                    <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ __('Notes') }}</p>
                    <p class="mt-1 text-sm">{{ $rfq->notes }}</p>
                </div>
            @endif
        </div>

        <div class="space-y-6 lg:col-span-2">
            {{-- Vendor comparison table --}}
            @if ($rfq->vendors->isNotEmpty())
                <div class="scf-card">
                    <flux:heading size="lg" class="mb-4">{{ __('Vendor responses') }}</flux:heading>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700 text-left">
                                    <th class="pb-2 pr-4 font-medium text-zinc-500">{{ __('Vendor') }}</th>
                                    <th class="pb-2 pr-4 font-medium text-zinc-500">{{ __('Status') }}</th>
                                    <th class="pb-2 pr-4 font-medium text-zinc-500 text-right">{{ __('Quoted total') }}</th>
                                    <th class="pb-2 pr-4 font-medium text-zinc-500 text-right">{{ __('Tax') }}</th>
                                    <th class="pb-2 pr-4 font-medium text-zinc-500">{{ __('Notes') }}</th>
                                    <th class="pb-2 font-medium text-zinc-500 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($rfq->vendors as $vendor)
                                    <tr @class(['bg-green-50 dark:bg-green-900/10' => $vendor->is_selected])>
                                        <td class="py-2 pr-4 font-medium">
                                            {{ $vendor->contact?->name ?? '—' }}
                                            @if ($vendor->is_selected)
                                                <flux:badge size="sm" color="green" class="ml-1">{{ __('Selected') }}</flux:badge>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-4">
                                            <flux:badge size="sm" :color="$vendor->status === 'responded' ? 'blue' : 'zinc'">
                                                {{ ucfirst($vendor->status) }}
                                            </flux:badge>
                                        </td>
                                        <td class="py-2 pr-4 text-right">
                                            {{ $vendor->quoted_total ? number_format((float) $vendor->quoted_total, 2) : '—' }}
                                        </td>
                                        <td class="py-2 pr-4 text-right">
                                            {{ $vendor->quoted_tax ? number_format((float) $vendor->quoted_tax, 2) : '—' }}
                                        </td>
                                        <td class="py-2 pr-4 text-xs text-zinc-500 max-w-xs truncate">{{ $vendor->notes ?? '—' }}</td>
                                        <td class="py-2 text-right">
                                            <div class="flex justify-end gap-1">
                                                @can('rfqs.update')
                                                    @if (in_array($rfq->status, [\App\Enums\RfqStatus::Sent, \App\Enums\RfqStatus::VendorResponse], true))
                                                        <flux:button size="sm" variant="ghost"
                                                            wire:click="openVendorResponseModal({{ $vendor->contact_id }})">
                                                            {{ __('Record') }}
                                                        </flux:button>
                                                    @endif
                                                @endcan
                                                @can('rfqs.accept')
                                                    @if ($rfq->status === \App\Enums\RfqStatus::VendorResponse && $vendor->status === 'responded' && ! $vendor->is_selected)
                                                        <flux:button size="sm" variant="filled"
                                                            wire:click="openAcceptModal({{ $vendor->contact_id }})">
                                                            {{ __('Accept') }}
                                                        </flux:button>
                                                    @endif
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Line items --}}
            <div class="scf-card">
                <flux:heading size="lg" class="mb-4">{{ __('Line items') }}</flux:heading>
                @if ($rfq->lines->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700 text-left">
                                    <th class="pb-2 pr-4 font-medium text-zinc-500">{{ __('Description') }}</th>
                                    <th class="pb-2 pr-4 font-medium text-zinc-500 text-right">{{ __('Qty') }}</th>
                                    <th class="pb-2 pr-4 font-medium text-zinc-500 text-right">{{ __('Unit price') }}</th>
                                    <th class="pb-2 font-medium text-zinc-500 text-right">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($rfq->lines as $line)
                                    <tr>
                                        <td class="py-2 pr-4">
                                            @if ($line->product)<p class="font-medium">{{ $line->product->name }}</p>@endif
                                            @if ($line->description)<p class="text-xs text-zinc-500">{{ $line->description }}</p>@endif
                                        </td>
                                        <td class="py-2 pr-4 text-right">{{ $line->quantity }}</td>
                                        <td class="py-2 pr-4 text-right">{{ number_format((float) $line->unit_price, 2) }}</td>
                                        <td class="py-2 text-right font-medium">{{ number_format((float) $line->line_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t border-zinc-200 dark:border-zinc-700">
                                <tr class="font-semibold">
                                    <td colspan="3" class="pt-3 pr-4 text-right">{{ __('Total') }}</td>
                                    <td class="pt-3 text-right">{{ $rfq->currency_code }} {{ number_format((float) $rfq->total_amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-zinc-500">{{ __('No line items.') }}</p>
                @endif
            </div>

            <x-purchasing.related-documents
                :purchase-request="$rfq->purchaseRequest"
                :rfq="null"
                :purchase-order="$rfq->convertedPurchaseOrder"
                :bill="null"
                :payment="null"
            />

            <livewire:activity.activity-timeline :subject="$rfq" :key="'activity-rfq-'.$rfq->id" />
        </div>
    </div>

    {{-- Vendor response modal --}}
    <flux:modal wire:model="showVendorResponseModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Record vendor response') }}</flux:heading>
            <flux:input wire:model="vendorQuotedTotal" type="number" step="0.01" :label="__('Quoted total')" required />
            <flux:input wire:model="vendorQuotedTax" type="number" step="0.01" :label="__('Quoted tax (optional)')" />
            <flux:textarea wire:model="vendorNotes" :label="__('Notes (optional)')" rows="2" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button variant="primary" wire:click="recordVendorResponse">{{ __('Save response') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Accept modal --}}
    <flux:modal wire:model="showAcceptModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Accept vendor') }}</flux:heading>
            @php $acceptVendor = $rfq->vendors->firstWhere('contact_id', $acceptVendorId); @endphp
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Accept quote from') }} <strong>{{ $acceptVendor?->contact?->name }}</strong>
                @if ($acceptVendor?->quoted_total)
                    ({{ number_format((float) $acceptVendor->quoted_total, 2) }})
                @endif?
            </p>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button variant="primary" wire:click="accept">{{ __('Accept') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Reject modal --}}
    <flux:modal wire:model="showRejectModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Reject RFQ') }}</flux:heading>
            <flux:textarea wire:model="rejectReason" :label="__('Reason (optional)')" rows="3" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button variant="danger" wire:click="reject">{{ __('Reject') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Cancel modal --}}
    <flux:modal wire:model="showCancelModal" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Cancel RFQ') }}</flux:heading>
            <flux:textarea wire:model="cancelReason" :label="__('Reason (optional)')" rows="3" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Back') }}</flux:button></flux:modal.close>
                <flux:button variant="danger" wire:click="cancel">{{ __('Cancel RFQ') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
