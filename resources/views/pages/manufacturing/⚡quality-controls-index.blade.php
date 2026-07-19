<?php

use App\Models\QualityControl;
use App\Enums\QualityControlStatus;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Quality control')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $qualityControlToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function qualityControls()
    {
        return QualityControl::query()
            ->with(['productionOrder', 'product'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('status', 'like', "%{$this->search}%")
                        ->orWhere('notes', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->latest()
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->qualityControlToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteQualityControl(): void
    {
        if ($this->qualityControlToDelete === null) {
            return;
        }

        $model = QualityControl::query()->findOrFail($this->qualityControlToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->qualityControlToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Quality control deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Quality control')"
        :subtitle="__('Manage Quality control')"
    >
        <x-slot:actions>
            <flux:button :href="route('quality-controls.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

        <flux:select wire:model.live="status" :placeholder="__('All statuses')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (QualityControlStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->qualityControls">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference Number') }}</flux:table.column>
                <flux:table.column>{{ __('Product Id') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Production Order Id') }}</flux:table.column>
                <flux:table.column>{{ __('Inspection Date') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->qualityControls as $qualityControl)
                    <flux:table.row wire:key="quality-controls-{{ $qualityControl->id }}">
                        <flux:table.cell>{{ $qualityControl->reference_number ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $qualityControl->product?->name ?? $qualityControl->product?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$qualityControl->status->color()">{{ $qualityControl->status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell>{{ $qualityControl->productionOrder?->name ?? $qualityControl->productionOrder?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $qualityControl->inspection_date?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('quality-controls.edit', $qualityControl)" wire:navigate />
                                @can('delete', $qualityControl)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $qualityControl->id }})" />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <x-empty-state icon="inbox" :title="__('No records found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure? This action cannot be undone.') }}</flux:text>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button variant="danger" wire:click="deleteQualityControl">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
