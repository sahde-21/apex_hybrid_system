<?php

use App\Models\TaxRate;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Tax rates')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $taxRateToDelete = null;

    public bool $showDeleteModal = false;

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, TaxRate>
     */
    #[Computed]
    public function taxRates()
    {
        return TaxRate::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('code', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
                });
            })
            ->latest()
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $taxRateId): void
    {
        $this->taxRateToDelete = $taxRateId;
        $this->showDeleteModal = true;
    }

    public function deleteTaxRate(): void
    {
        if ($this->taxRateToDelete === null) {
            return;
        }

        $model = TaxRate::query()->findOrFail($this->taxRateToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->taxRateToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Tax rate deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Tax rates')"
        :subtitle="__('Configure tax rates for billing and accounting')"
    >
        <x-slot:actions>
            <flux:button :href="route('tax-rates.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add tax rate') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by name, code, or description...')" />
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->taxRates">
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Code') }}</flux:table.column>
                <flux:table.column>{{ __('Rate') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Description') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->taxRates as $taxRate)
                    <flux:table.row wire:key="tax-rate-{{ $taxRate->id }}">
                        <flux:table.cell class="font-medium">{{ $taxRate->name }}</flux:table.cell>
                        <flux:table.cell>{{ $taxRate->code }}</flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $taxRate->rate, 2) }}%</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$taxRate->is_active ? 'green' : 'zinc'">
                                {{ $taxRate->is_active ? __('Active') : __('Inactive') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $taxRate->description ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                
@can('update', $taxRate)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil-square"
                                    :href="route('tax-rates.edit', $taxRate)"
                                    wire:navigate
                                />
                                
@endcan
                                @can('delete', $taxRate)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    wire:click="confirmDelete({{ $taxRate->id }})"
                                />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <x-empty-state icon="inbox" :title="__('No tax rates found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete tax rate') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure you want to delete this tax rate? This action cannot be undone.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteTaxRate">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
