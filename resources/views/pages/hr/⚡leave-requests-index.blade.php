<?php

use App\Models\LeaveRequest;
use App\Enums\LeaveRequestStatus;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Leave requests')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $leaveRequestToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function leaveRequests()
    {
        return LeaveRequest::query()
            ->with(['employee'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('leave_type', 'like', "%{$this->search}%")
                        ->orWhere('leave_type', 'like', "%{$this->search}%")
                        ->orWhere('status', 'like', "%{$this->search}%")
                        ->orWhere('reason', 'like', "%{$this->search}%");
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
        $this->leaveRequestToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteLeaveRequest(): void
    {
        if ($this->leaveRequestToDelete === null) {
            return;
        }

        $model = LeaveRequest::query()->findOrFail($this->leaveRequestToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->leaveRequestToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Leave requests deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Leave requests')"
        :subtitle="__('Manage Leave requests')"
    >
        <x-slot:actions>
            <flux:button :href="route('leave-requests.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

        <flux:select wire:model.live="status" :placeholder="__('All statuses')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (LeaveRequestStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->leaveRequests">
            <flux:table.columns>
                <flux:table.column>{{ __('Employee Id') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Leave Type') }}</flux:table.column>
                <flux:table.column>{{ __('Start Date') }}</flux:table.column>
                <flux:table.column>{{ __('End Date') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->leaveRequests as $leaveRequest)
                    <flux:table.row wire:key="leave-requests-{{ $leaveRequest->id }}">
                        <flux:table.cell>{{ $leaveRequest->employee?->name ?? $leaveRequest->employee?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$leaveRequest->status->color()">{{ $leaveRequest->status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell>{{ $leaveRequest->leave_type ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $leaveRequest->start_date?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $leaveRequest->end_date?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('leave-requests.edit', $leaveRequest)" wire:navigate />
                                @can('delete', $leaveRequest)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $leaveRequest->id }})" />
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
                <flux:button variant="danger" wire:click="deleteLeaveRequest">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
