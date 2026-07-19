<?php

use App\Models\TimeLog;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Time logs')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $timeLogToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function timeLogs()
    {
        return TimeLog::query()
            ->with(['projectTask', 'employee'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('description', 'like', "%{$this->search}%")
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

    public function confirmDelete(int $id): void
    {
        $this->timeLogToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteTimeLog(): void
    {
        if ($this->timeLogToDelete === null) {
            return;
        }

        $model = TimeLog::query()->findOrFail($this->timeLogToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->timeLogToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Time logs deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Time logs')"
        :subtitle="__('Manage Time logs')"
    >
        <x-slot:actions>
            <flux:button :href="route('time-logs.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->timeLogs">
            <flux:table.columns>
                <flux:table.column>{{ __('Employee Id') }}</flux:table.column>
                <flux:table.column>{{ __('Project Task Id') }}</flux:table.column>
                <flux:table.column>{{ __('Log Date') }}</flux:table.column>
                <flux:table.column>{{ __('Hours') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->timeLogs as $timeLog)
                    <flux:table.row wire:key="time-logs-{{ $timeLog->id }}">
                        <flux:table.cell>{{ $timeLog->employee?->name ?? $timeLog->employee?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $timeLog->projectTask?->name ?? $timeLog->projectTask?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $timeLog->log_date?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $timeLog->hours, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('time-logs.edit', $timeLog)" wire:navigate />
                                @can('delete', $timeLog)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $timeLog->id }})" />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
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
                <flux:button variant="danger" wire:click="deleteTimeLog">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
