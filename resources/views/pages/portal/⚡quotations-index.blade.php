<?php

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Services\Portal\PortalService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

new #[Layout('layouts.portal')] #[Title('Quotations')] class extends \App\Livewire\ConcernBases\ScopesToPortalContactBase {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function accept(int $id, PortalService $portal): void
    {
        $quotation = Quotation::query()->findOrFail($id);
        $this->assertOwns($quotation);
        abort_unless(in_array($quotation->status, [QuotationStatus::Sent, QuotationStatus::Draft], true), 422);
        $quotation->update(['status' => QuotationStatus::Accepted]);
        $portal->notify(auth('portal')->user(), 'quotation.accepted', __('scf.portal.quotation_accepted'), $quotation->reference_number, route('portal.quotations.show', $quotation));
        Flux::toast(variant: 'success', text: __('scf.portal.quotation_accepted'));
    }

    public function reject(int $id, PortalService $portal): void
    {
        $quotation = Quotation::query()->findOrFail($id);
        $this->assertOwns($quotation);
        abort_unless(in_array($quotation->status, [QuotationStatus::Sent, QuotationStatus::Draft], true), 422);
        $quotation->update(['status' => QuotationStatus::Rejected]);
        $portal->notify(auth('portal')->user(), 'quotation.rejected', __('scf.portal.quotation_rejected'), $quotation->reference_number, route('portal.quotations.show', $quotation));
        Flux::toast(variant: 'success', text: __('scf.portal.quotation_rejected'));
    }

    #[Computed]
    public function quotations()
    {
        return $this->scopeOwned(Quotation::query())
            ->when($this->search, fn ($q) => $q->where('reference_number', 'like', "%{$this->search}%"))
            ->whereIn('status', [QuotationStatus::Sent, QuotationStatus::Accepted, QuotationStatus::Rejected, QuotationStatus::Expired])
            ->latest('quotation_date')
            ->paginate(10);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <flux:heading size="lg">{{ __('scf.portal.quotations') }}</flux:heading>
        <div class="mt-4">
            <flux:input class="max-w-md" wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />
        </div>
    </div>

    <div class="portal-glass overflow-hidden rounded-2xl">
        <flux:table :paginate="$this->quotations">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Valid until') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Total') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->quotations as $quotation)
                    <flux:table.row wire:key="q-{{ $quotation->id }}">
                        <flux:table.cell class="font-medium">{{ $quotation->reference_number }}</flux:table.cell>
                        <flux:table.cell>{{ $quotation->quotation_date?->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>{{ $quotation->valid_until?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$quotation->status->color()">{{ $quotation->status->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums">{{ number_format((float) $quotation->total_amount, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1">
                                <flux:button size="sm" variant="ghost" :href="route('portal.quotations.show', $quotation)" wire:navigate>{{ __('View') }}</flux:button>
                                @if (in_array($quotation->status, [\App\Enums\QuotationStatus::Sent, \App\Enums\QuotationStatus::Draft], true))
                                    <flux:button size="sm" variant="primary" wire:click="accept({{ $quotation->id }})">{{ __('Accept') }}</flux:button>
                                    <flux:button size="sm" variant="danger" wire:click="reject({{ $quotation->id }})">{{ __('Reject') }}</flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <x-empty-state icon="document-duplicate" :title="__('scf.portal.no_quotations')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</section>
