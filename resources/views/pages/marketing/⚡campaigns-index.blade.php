<?php

use App\Models\Campaign;
use App\Enums\CampaignStatus;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Campaigns')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $campaignToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function campaigns()
    {
        return Campaign::query()
            
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%")
                        ->orWhere('code', 'like', "%{$this->search}%")
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
        $this->campaignToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteCampaign(): void
    {
        if ($this->campaignToDelete === null) {
            return;
        }

        $model = Campaign::query()->findOrFail($this->campaignToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->campaignToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Campaigns deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Campaigns')"
        :subtitle="__('Manage Campaigns')"
    >
        <x-slot:actions>
            <flux:button :href="route('campaigns.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

        <flux:select wire:model.live="status" :placeholder="__('All statuses')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (CampaignStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->campaigns">
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Code') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Start Date') }}</flux:table.column>
                <flux:table.column>{{ __('End Date') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->campaigns as $campaign)
                    <flux:table.row wire:key="campaigns-{{ $campaign->id }}">
                        <flux:table.cell>{{ $campaign->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $campaign->code ?? '—' }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$campaign->status->color()">{{ $campaign->status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell>{{ $campaign->start_date?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $campaign->end_date?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('campaigns.edit', $campaign)" wire:navigate />
                                @can('delete', $campaign)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $campaign->id }})" />
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
                <flux:button variant="danger" wire:click="deleteCampaign">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
