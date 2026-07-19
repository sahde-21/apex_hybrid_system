<?php

use App\Models\FloorPlan;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Floor plans')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $floorPlanToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function floorPlans()
    {
        return FloorPlan::query()
            ->with(['warehouse', 'branch'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%");
                });
            })
            
            ->latest()
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->floorPlanToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteFloorPlan(): void
    {
        if ($this->floorPlanToDelete === null) {
            return;
        }

        $model = FloorPlan::query()->findOrFail($this->floorPlanToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->floorPlanToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Floor plans deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Floor plans')"
        :subtitle="__('Manage Floor plans')"
    >
        <x-slot:actions>
            <flux:button :href="route('floor-plans.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->floorPlans">
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Warehouse Id') }}</flux:table.column>
                <flux:table.column>{{ __('Branch Id') }}</flux:table.column>
                <flux:table.column>{{ __('Width') }}</flux:table.column>
                <flux:table.column>{{ __('Height') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->floorPlans as $floorPlan)
                    <flux:table.row wire:key="floor-plans-{{ $floorPlan->id }}">
                        <flux:table.cell>{{ $floorPlan->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $floorPlan->warehouse?->name ?? $floorPlan->warehouse?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $floorPlan->branch?->name ?? $floorPlan->branch?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $floorPlan->width ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $floorPlan->height ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('floor-plans.edit', $floorPlan)" wire:navigate />
                                @can('delete', $floorPlan)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $floorPlan->id }})" />
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
                <flux:button variant="danger" wire:click="deleteFloorPlan">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
