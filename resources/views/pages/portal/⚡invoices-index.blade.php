<?php

use App\Models\Invoice;
use App\Support\ScopesToPortalContact;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.portal')] #[Title('Invoices')] class extends Component {
    use ScopesToPortalContact;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Computed]
    public function invoices()
    {
        return $this->scopeOwned(Invoice::query())
            ->when($this->search, fn ($q) => $q->where('reference_number', 'like', "%{$this->search}%"))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->whereNot('status', 'draft')
            ->latest('invoice_date')
            ->paginate(10);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <flux:heading size="lg">{{ __('scf.portal.invoices') }}</flux:heading>
        <div class="mt-4 flex flex-wrap gap-3">
            <flux:input class="min-w-64 flex-1" wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />
            <flux:select wire:model.live="status" class="w-44">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (['sent', 'paid', 'overdue', 'cancelled'] as $status)
                    <option value="{{ $status }}">{{ __(ucfirst($status)) }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="portal-glass overflow-hidden rounded-2xl">
        <flux:table :paginate="$this->invoices">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Due') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Total') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->invoices as $invoice)
                    <flux:table.row wire:key="inv-{{ $invoice->id }}">
                        <flux:table.cell class="font-medium">{{ $invoice->reference_number }}</flux:table.cell>
                        <flux:table.cell>{{ $invoice->invoice_date?->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>{{ $invoice->due_date?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$invoice->status->color()">{{ $invoice->status->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums">{{ number_format((float) $invoice->total_amount, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="sm" variant="ghost" :href="route('portal.invoices.show', $invoice)" wire:navigate>{{ __('View') }}</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <x-empty-state icon="receipt-percent" :title="__('scf.portal.no_invoices')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</section>
