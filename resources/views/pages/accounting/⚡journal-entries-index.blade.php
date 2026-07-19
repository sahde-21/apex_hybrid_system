<?php

use App\Enums\JournalEntryStatus;
use App\Models\JournalEntry;
use App\Services\Accounting\JournalEngineService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Journal entries')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $journalEntryToDelete = null;

    public bool $showDeleteModal = false;

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, JournalEntry>
     */
    #[Computed]
    public function journalEntries()
    {
        return JournalEntry::query()
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('reference_number', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhere('notes', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->latest('entry_date')
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

    public function postEntry(int $id): void
    {
        $entry = JournalEntry::query()->findOrFail($id);
        $this->authorize('post', $entry);
        app(JournalEngineService::class)->post($entry, auth()->user());
        unset($this->journalEntries);
        Flux::toast(variant: 'success', text: __('Posted'));
    }

    public function reverseEntry(int $id): void
    {
        $entry = JournalEntry::query()->findOrFail($id);
        $this->authorize('reverse', $entry);
        app(JournalEngineService::class)->reverse($entry, auth()->user());
        unset($this->journalEntries);
        Flux::toast(variant: 'success', text: __('scf.accounting_engine.reversed'));
    }

    public function confirmDelete(int $journalEntryId): void
    {
        $this->journalEntryToDelete = $journalEntryId;
        $this->showDeleteModal = true;
    }

    public function deleteJournalEntry(): void
    {
        if ($this->journalEntryToDelete === null) {
            return;
        }

        $model = JournalEntry::query()->findOrFail($this->journalEntryToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->journalEntryToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Journal entry deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Journal entries')"
        :subtitle="__('Manage general ledger journal entries')"
    >
        <x-slot:actions>
            <flux:button :href="route('journal-entries.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add journal entry') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by reference, description, or notes...')" />

        <flux:select wire:model.live="status" :placeholder="__('All statuses')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (JournalEntryStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->journalEntries">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Description') }}</flux:table.column>
                <flux:table.column>{{ __('Entry date') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Debit') }}</flux:table.column>
                <flux:table.column>{{ __('Credit') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->journalEntries as $journalEntry)
                    <flux:table.row wire:key="journal-entry-{{ $journalEntry->id }}">
                        <flux:table.cell class="font-medium">{{ $journalEntry->reference_number }}</flux:table.cell>
                        <flux:table.cell>{{ $journalEntry->description }}</flux:table.cell>
                        <flux:table.cell>{{ $journalEntry->entry_date->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$journalEntry->status->color()">{{ $journalEntry->status->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $journalEntry->total_debit, 2) }}</flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $journalEntry->total_credit, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                @can('post', $journalEntry)
                                    <flux:button size="sm" variant="ghost" wire:click="postEntry({{ $journalEntry->id }})">{{ __('scf.accounting_engine.post') }}</flux:button>
                                @endcan
                                @can('reverse', $journalEntry)
                                    <flux:button size="sm" variant="ghost" wire:click="reverseEntry({{ $journalEntry->id }})">{{ __('scf.accounting_engine.reverse') }}</flux:button>
                                @endcan
                                @can('update', $journalEntry)
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="pencil-square"
                                        :href="route('journal-entries.edit', $journalEntry)"
                                        wire:navigate
                                    />
                                @endcan
                                @can('delete', $journalEntry)
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="trash"
                                        wire:click="confirmDelete({{ $journalEntry->id }})"
                                    />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">
                            <x-empty-state icon="inbox" :title="__('No journal entries found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete journal entry') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure you want to delete this journal entry? This action cannot be undone.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteJournalEntry">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
