<?php

use App\Models\PerformanceReview;
use App\Enums\PerformanceReviewStatus;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Performance reviews')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $performanceReviewToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function performanceReviews()
    {
        return PerformanceReview::query()
            ->with(['employee'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('status', 'like', "%{$this->search}%")
                        ->orWhere('status', 'like', "%{$this->search}%")
                        ->orWhere('comments', 'like', "%{$this->search}%");
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
        $this->performanceReviewToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deletePerformanceReview(): void
    {
        if ($this->performanceReviewToDelete === null) {
            return;
        }

        $model = PerformanceReview::query()->findOrFail($this->performanceReviewToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->performanceReviewToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Performance reviews deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Performance reviews')"
        :subtitle="__('Manage Performance reviews')"
    >
        <x-slot:actions>
            <flux:button :href="route('performance-reviews.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

        <flux:select wire:model.live="status" :placeholder="__('All statuses')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (PerformanceReviewStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->performanceReviews">
            <flux:table.columns>
                <flux:table.column>{{ __('Employee Id') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Review Date') }}</flux:table.column>
                <flux:table.column>{{ __('Rating') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->performanceReviews as $performanceReview)
                    <flux:table.row wire:key="performance-reviews-{{ $performanceReview->id }}">
                        <flux:table.cell>{{ $performanceReview->employee?->name ?? $performanceReview->employee?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$performanceReview->status->color()">{{ $performanceReview->status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell>{{ $performanceReview->review_date?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $performanceReview->rating ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('performance-reviews.edit', $performanceReview)" wire:navigate />
                                @can('delete', $performanceReview)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $performanceReview->id }})" />
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
                <flux:button variant="danger" wire:click="deletePerformanceReview">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
