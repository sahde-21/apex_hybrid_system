<?php

use App\Models\Shift;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Shift management')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $shiftToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function shifts()
    {
        return Shift::query()
            ->with(['branch'])
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
        $this->shiftToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteShift(): void
    {
        if ($this->shiftToDelete === null) {
            return;
        }

        $model = Shift::query()->findOrFail($this->shiftToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->shiftToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Shift management deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Shift management')"
        :subtitle="__('Manage Shift management')"
    >
        <x-slot:actions>
            <flux:button :href="route('shifts.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->shifts">
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Branch Id') }}</flux:table.column>
                <flux:table.column>{{ __('Start Time') }}</flux:table.column>
                <flux:table.column>{{ __('End Time') }}</flux:table.column>
                <flux:table.column>{{ __('Is Active') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->shifts as $shift)
                    <flux:table.row wire:key="shifts-{{ $shift->id }}">
                        <flux:table.cell>{{ $shift->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $shift->branch?->name ?? $shift->branch?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $shift->start_time ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $shift->end_time ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $shift->is_active ? __('Yes') : __('No') }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('shifts.edit', $shift)" wire:navigate />
                                @can('delete', $shift)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $shift->id }})" />
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
                <flux:button variant="danger" wire:click="deleteShift">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
