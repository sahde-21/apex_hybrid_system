<?php

use App\Models\GiftCard;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Gift cards')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $giftCardToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function giftCards()
    {
        return GiftCard::query()
            ->with(['contact'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('code', 'like', "%{$this->search}%")
                        ->orWhere('code', 'like', "%{$this->search}%");
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
        $this->giftCardToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteGiftCard(): void
    {
        if ($this->giftCardToDelete === null) {
            return;
        }

        $model = GiftCard::query()->findOrFail($this->giftCardToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->giftCardToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Gift cards deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Gift cards')"
        :subtitle="__('Manage Gift cards')"
    >
        <x-slot:actions>
            <flux:button :href="route('gift-cards.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->giftCards">
            <flux:table.columns>
                <flux:table.column>{{ __('Code') }}</flux:table.column>
                <flux:table.column>{{ __('Contact Id') }}</flux:table.column>
                <flux:table.column>{{ __('Initial Balance') }}</flux:table.column>
                <flux:table.column>{{ __('Current Balance') }}</flux:table.column>
                <flux:table.column>{{ __('Expires At') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->giftCards as $giftCard)
                    <flux:table.row wire:key="gift-cards-{{ $giftCard->id }}">
                        <flux:table.cell>{{ $giftCard->code ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $giftCard->contact?->name ?? $giftCard->contact?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $giftCard->initial_balance, 2) }}</flux:table.cell>
                        <flux:table.cell>{{ number_format((float) $giftCard->current_balance, 2) }}</flux:table.cell>
                        <flux:table.cell>{{ $giftCard->expires_at?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('gift-cards.edit', $giftCard)" wire:navigate />
                                @can('delete', $giftCard)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $giftCard->id }})" />
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
                <flux:button variant="danger" wire:click="deleteGiftCard">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
