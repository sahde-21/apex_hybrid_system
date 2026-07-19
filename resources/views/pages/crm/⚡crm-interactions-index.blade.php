<?php

use App\Enums\CrmInteractionType;
use App\Models\CrmInteraction;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('CRM interactions')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $type = '';

    public ?int $crmInteractionToDelete = null;

    public bool $showDeleteModal = false;

    /**
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, CrmInteraction>
     */
    #[Computed]
    public function crmInteractions()
    {
        return CrmInteraction::query()
            ->with('contact')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('subject', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhereHas('contact', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->type, fn ($query) => $query->where('interaction_type', $this->type))
            ->latest('interaction_date')
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $crmInteractionId): void
    {
        $this->crmInteractionToDelete = $crmInteractionId;
        $this->showDeleteModal = true;
    }

    public function deleteCrmInteraction(): void
    {
        if ($this->crmInteractionToDelete === null) {
            return;
        }

        $model = CrmInteraction::query()->findOrFail($this->crmInteractionToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->crmInteractionToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('CRM interaction deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('CRM interactions')"
        :subtitle="__('Track customer calls, emails, meetings, and notes')"
    >
        <x-slot:actions>
            <flux:button :href="route('crm-interactions.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add CRM interaction') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search by subject, description, or contact...')" />

        <flux:select wire:model.live="type" :placeholder="__('All types')">
            <flux:select.option value="">{{ __('All types') }}</flux:select.option>
            @foreach (CrmInteractionType::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->crmInteractions">
            <flux:table.columns>
                <flux:table.column>{{ __('Subject') }}</flux:table.column>
                <flux:table.column>{{ __('Contact') }}</flux:table.column>
                <flux:table.column>{{ __('Type') }}</flux:table.column>
                <flux:table.column>{{ __('Interaction date') }}</flux:table.column>
                <flux:table.column>{{ __('Follow-up date') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->crmInteractions as $crmInteraction)
                    <flux:table.row wire:key="crm-interaction-{{ $crmInteraction->id }}">
                        <flux:table.cell class="font-medium">{{ $crmInteraction->subject }}</flux:table.cell>
                        <flux:table.cell>{{ $crmInteraction->contact?->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$crmInteraction->interaction_type->color()">{{ $crmInteraction->interaction_type->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $crmInteraction->interaction_date->format('Y-m-d H:i') }}</flux:table.cell>
                        <flux:table.cell>{{ $crmInteraction->follow_up_date?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                
@can('update', $crmInteraction)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="pencil-square"
                                    :href="route('crm-interactions.edit', $crmInteraction)"
                                    wire:navigate
                                />
                                
@endcan
                                @can('delete', $crmInteraction)
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    wire:click="confirmDelete({{ $crmInteraction->id }})"
                                />
                                @endcan
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <x-empty-state icon="inbox" :title="__('No CRM interactions found.')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Delete CRM interaction') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Are you sure you want to delete this CRM interaction? This action cannot be undone.') }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteCrmInteraction">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
