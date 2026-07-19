<?php

use App\Models\ProjectTask;
use App\Enums\ProjectTaskStatus;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Project tasks')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $projectTaskToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function projectTasks()
    {
        return ProjectTask::query()
            ->with(['contract', 'employee'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('title', 'like', "%{$this->search}%")
                        ->orWhere('title', 'like', "%{$this->search}%")
                        ->orWhere('priority', 'like', "%{$this->search}%")
                        ->orWhere('status', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%");
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
        $this->projectTaskToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteProjectTask(): void
    {
        if ($this->projectTaskToDelete === null) {
            return;
        }

        $model = ProjectTask::query()->findOrFail($this->projectTaskToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->projectTaskToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Project tasks deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Project tasks')"
        :subtitle="__('Manage Project tasks')"
    >
        <x-slot:actions>
            <flux:button :href="route('project-tasks.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

        <flux:select wire:model.live="status" :placeholder="__('All statuses')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (ProjectTaskStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->projectTasks">
            <flux:table.columns>
                <flux:table.column>{{ __('Title') }}</flux:table.column>
                <flux:table.column>{{ __('Employee Id') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Contract Id') }}</flux:table.column>
                <flux:table.column>{{ __('Due Date') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->projectTasks as $projectTask)
                    <flux:table.row wire:key="project-tasks-{{ $projectTask->id }}">
                        <flux:table.cell>{{ $projectTask->title ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $projectTask->employee?->name ?? $projectTask->employee?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$projectTask->status->color()">{{ $projectTask->status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell>{{ $projectTask->contract?->name ?? $projectTask->contract?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $projectTask->due_date?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('project-tasks.edit', $projectTask)" wire:navigate />
                                @can('delete', $projectTask)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $projectTask->id }})" />
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
                <flux:button variant="danger" wire:click="deleteProjectTask">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
