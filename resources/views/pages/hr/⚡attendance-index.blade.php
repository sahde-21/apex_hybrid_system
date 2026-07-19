<?php

use App\Models\Attendance;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Attendance')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $attendanceToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function attendances()
    {
        return Attendance::query()
            ->with(['employee', 'branch'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('status', 'like', "%{$this->search}%")
                        ->orWhere('status', 'like', "%{$this->search}%")
                        ->orWhere('notes', 'like', "%{$this->search}%");
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
        $this->attendanceToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteAttendance(): void
    {
        if ($this->attendanceToDelete === null) {
            return;
        }

        $model = Attendance::query()->findOrFail($this->attendanceToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->attendanceToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Attendance deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Attendance')"
        :subtitle="__('Manage Attendance')"
    >
        <x-slot:actions>
            <flux:button :href="route('attendance.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->attendances">
            <flux:table.columns>
                <flux:table.column>{{ __('Employee Id') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Branch Id') }}</flux:table.column>
                <flux:table.column>{{ __('Attendance Date') }}</flux:table.column>
                <flux:table.column>{{ __('Check In') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->attendances as $attendance)
                    <flux:table.row wire:key="attendance-{{ $attendance->id }}">
                        <flux:table.cell>{{ $attendance->employee?->name ?? $attendance->employee?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$attendance->status->color()">{{ $attendance->status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell>{{ $attendance->branch?->name ?? $attendance->branch?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $attendance->attendance_date?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $attendance->check_in ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('attendance.edit', $attendance)" wire:navigate />
                                @can('delete', $attendance)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $attendance->id }})" />
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
                <flux:button variant="danger" wire:click="deleteAttendance">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
