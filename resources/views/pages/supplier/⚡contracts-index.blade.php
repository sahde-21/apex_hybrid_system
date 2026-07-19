<?php

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Support\ScopesToSupplierContact;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.supplier')] #[Title('Contracts')] class extends Component {
    use ScopesToSupplierContact;
    use WithPagination;

    #[Computed]
    public function contracts()
    {
        return $this->scopeOwned(Contract::query())
            ->where('status', '!=', ContractStatus::Draft)
            ->latest('end_date')
            ->paginate(10);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <flux:heading size="lg">{{ __('scf.supplier_portal.contracts') }}</flux:heading>
        <p class="mt-1 text-sm text-zinc-500">{{ __('scf.supplier_portal.contracts_subtitle') }}</p>
    </div>

    <div class="portal-glass overflow-hidden rounded-2xl">
        <flux:table :paginate="$this->contracts">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Title') }}</flux:table.column>
                <flux:table.column>{{ __('Start') }}</flux:table.column>
                <flux:table.column>{{ __('Expires') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Value') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->contracts as $contract)
                    <flux:table.row wire:key="ctr-{{ $contract->id }}">
                        <flux:table.cell class="font-medium">
                            <a href="{{ route('supplier.contracts.show', $contract) }}" class="hover:underline" wire:navigate>{{ $contract->reference_number }}</a>
                        </flux:table.cell>
                        <flux:table.cell>{{ $contract->title }}</flux:table.cell>
                        <flux:table.cell>{{ $contract->start_date?->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>{{ $contract->end_date?->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$contract->status->color()">{{ $contract->status->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums">{{ number_format((float) $contract->value, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-1">
                                <flux:button size="xs" :href="route('supplier.pdf', ['type' => 'contract', 'id' => $contract->id])" variant="ghost" target="_blank">PDF</flux:button>
                                <flux:button size="xs" :href="route('supplier.print', ['type' => 'contract', 'id' => $contract->id])" variant="ghost" target="_blank">{{ __('Print') }}</flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <x-empty-state icon="document-text" :title="__('scf.supplier_portal.no_contracts')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</section>
