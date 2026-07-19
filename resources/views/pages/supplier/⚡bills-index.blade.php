<?php

use App\Enums\BillStatus;
use App\Models\Bill;
use App\Support\ScopesToSupplierContact;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.supplier')] #[Title('Bills')] class extends Component {
    use ScopesToSupplierContact;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Computed]
    public function bills()
    {
        return $this->scopeOwned(Bill::query())
            ->when($this->search, fn ($q) => $q->where('reference_number', 'like', "%{$this->search}%"))
            ->where('status', '!=', BillStatus::Draft)
            ->latest('bill_date')
            ->paginate(10);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <flux:heading size="lg">{{ __('scf.supplier_portal.bills') }}</flux:heading>
        <div class="mt-4">
            <flux:input class="max-w-md" wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />
        </div>
    </div>

    <div class="portal-glass overflow-hidden rounded-2xl">
        <flux:table :paginate="$this->bills">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Due') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Total') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->bills as $bill)
                    <flux:table.row wire:key="bill-{{ $bill->id }}">
                        <flux:table.cell class="font-medium">
                            <a href="{{ route('supplier.bills.show', $bill) }}" class="hover:underline" wire:navigate>{{ $bill->reference_number }}</a>
                        </flux:table.cell>
                        <flux:table.cell>{{ $bill->bill_date?->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>{{ $bill->due_date?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$bill->status->color()">{{ $bill->status->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums">{{ number_format((float) $bill->total_amount, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-1">
                                <flux:button size="xs" :href="route('supplier.bills.show', $bill)" variant="ghost" wire:navigate>{{ __('View') }}</flux:button>
                                <flux:button size="xs" :href="route('supplier.pdf', ['type' => 'bill', 'id' => $bill->id])" variant="ghost" target="_blank">PDF</flux:button>
                                <flux:button size="xs" :href="route('supplier.print', ['type' => 'bill', 'id' => $bill->id])" variant="ghost" target="_blank">{{ __('Print') }}</flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <x-empty-state icon="receipt-percent" :title="__('scf.supplier_portal.no_bills')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</section>
