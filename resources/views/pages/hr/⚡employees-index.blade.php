<?php

use App\Models\Employee;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Employees')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $employeeToDelete = null;

    public bool $showDeleteModal = false;

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, Employee>
     */
    #[Computed]
    public function employees()
    {
        return Employee::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('employee_number', 'like', "%{$this->search}%")
                        ->orWhere('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('department', 'like', "%{$this->search}%")
                        ->orWhere('job_title', 'like', "%{$this->search}%");
                });
            })
            ->latest('hire_date')
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $employeeId): void
    {
        $this->employeeToDelete = $employeeId;
        $this->showDeleteModal = true;
    }

    public function deleteEmployee(): void
    {
        if ($this->employeeToDelete === null) {
            return;
        }

        $model = Employee::query()->findOrFail($this->employeeToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->employeeToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Employee deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Employees')"
        :subtitle="__('Manage staff records and employment details')"
    >
        <x-slot:actions>
            <flux:button :href="route('employees.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add employee') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <x-module-toolbar>
        <x-slot:search>
            <flux:input class="min-w-64 flex-1" wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by employee number, name, email, department, or job title...')" />
        </x-slot:search>
    </x-module-toolbar>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->employees">
            <flux:table.columns>
                <flux:table.column>{{ __('Employee number') }}</flux:table.column>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Department') }}</flux:table.column>
                <flux:table.column>{{ __('Job title') }}</flux:table.column>
                <flux:table.column>{{ __('Hire date') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->employees as $employee)
                    <flux:table.row wire:key="employee-{{ $employee->id }}">
                        <flux:table.cell class="font-medium">{{ $employee->employee_number }}</flux:table.cell>
                        <flux:table.cell>{{ $employee->fullName() }}</flux:table.cell>
                        <flux:table.cell>{{ $employee->department ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $employee->job_title ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $employee->hire_date->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$employee->is_active ? 'green' : 'zinc'">
                                {{ $employee->is_active ? __('Active') : __('Inactive') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                
@can('update', $employee)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil-square"
                                    :href="route('employees.edit', $employee)"
                                    wire:navigate
                                />
                                
@endcan
                                @can('delete', $employee)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    wire:click="confirmDelete({{ $employee->id }})"
                                />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <x-empty-state icon="inbox" :title="__('No employees found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete employee') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure you want to delete this employee? This action cannot be undone.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteEmployee">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
