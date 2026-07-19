<?php

use App\Models\Ticket;
use App\Enums\TicketStatus;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Tickets')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $ticketToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function tickets()
    {
        return Ticket::query()
            ->with(['contact'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('subject', 'like', "%{$this->search}%")
                        ->orWhere('priority', 'like', "%{$this->search}%")
                        ->orWhere('status', 'like', "%{$this->search}%");
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
        $this->ticketToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteTicket(): void
    {
        if ($this->ticketToDelete === null) {
            return;
        }

        $model = Ticket::query()->findOrFail($this->ticketToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->ticketToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Tickets deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Tickets')"
        :subtitle="__('Manage Tickets')"
    >
        <x-slot:actions>
            <flux:button :href="route('tickets.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

        <flux:select wire:model.live="status" :placeholder="__('All statuses')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (TicketStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->tickets">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference Number') }}</flux:table.column>
                <flux:table.column>{{ __('Subject') }}</flux:table.column>
                <flux:table.column>{{ __('Contact Id') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->tickets as $ticket)
                    <flux:table.row wire:key="tickets-{{ $ticket->id }}">
                        <flux:table.cell>{{ $ticket->reference_number ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $ticket->subject ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $ticket->contact?->name ?? $ticket->contact?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$ticket->status->color()">{{ $ticket->status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('tickets.edit', $ticket)" wire:navigate />
                                @can('delete', $ticket)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $ticket->id }})" />
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
                <flux:button variant="danger" wire:click="deleteTicket">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
