<?php

use App\Models\CustomerFeedback;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Customer feedback')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $customerFeedbackToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function customerFeedbacks()
    {
        return CustomerFeedback::query()
            ->with(['contact'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('subject', 'like', "%{$this->search}%")
                        ->orWhere('subject', 'like', "%{$this->search}%")
                        ->orWhere('feedback', 'like', "%{$this->search}%");
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
        $this->customerFeedbackToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteCustomerFeedback(): void
    {
        if ($this->customerFeedbackToDelete === null) {
            return;
        }

        $model = CustomerFeedback::query()->findOrFail($this->customerFeedbackToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->customerFeedbackToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Customer feedback deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Customer feedback')"
        :subtitle="__('Manage Customer feedback')"
    >
        <x-slot:actions>
            <flux:button :href="route('customer-feedback.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->customerFeedbacks">
            <flux:table.columns>
                <flux:table.column>{{ __('Subject') }}</flux:table.column>
                <flux:table.column>{{ __('Contact Id') }}</flux:table.column>
                <flux:table.column>{{ __('Rating') }}</flux:table.column>
                <flux:table.column>{{ __('Feedback Date') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->customerFeedbacks as $customerFeedback)
                    <flux:table.row wire:key="customer-feedback-{{ $customerFeedback->id }}">
                        <flux:table.cell>{{ $customerFeedback->subject ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $customerFeedback->contact?->name ?? $customerFeedback->contact?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $customerFeedback->rating ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $customerFeedback->feedback_date?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('customer-feedback.edit', $customerFeedback)" wire:navigate />
                                @can('delete', $customerFeedback)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $customerFeedback->id }})" />
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
                <flux:button variant="danger" wire:click="deleteCustomerFeedback">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
