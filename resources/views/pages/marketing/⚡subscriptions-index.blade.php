<?php

use App\Models\Subscription;
use App\Enums\SubscriptionStatus;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Subscriptions')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    public ?int $subscriptionToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function subscriptions()
    {
        return Subscription::query()
            ->with(['contact'])
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('plan_name', 'like', "%{$this->search}%")
                        ->orWhere('plan_name', 'like', "%{$this->search}%")
                        ->orWhere('billing_cycle', 'like', "%{$this->search}%")
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
        $this->subscriptionToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteSubscription(): void
    {
        if ($this->subscriptionToDelete === null) {
            return;
        }

        $model = Subscription::query()->findOrFail($this->subscriptionToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->subscriptionToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Subscriptions deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Subscriptions')"
        :subtitle="__('Manage Subscriptions')"
    >
        <x-slot:actions>
            <flux:button :href="route('subscriptions.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

        <flux:select wire:model.live="status" :placeholder="__('All statuses')">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (SubscriptionStatus::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->subscriptions">
            <flux:table.columns>
                <flux:table.column>{{ __('Contact Id') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Plan Name') }}</flux:table.column>
                <flux:table.column>{{ __('Start Date') }}</flux:table.column>
                <flux:table.column>{{ __('End Date') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->subscriptions as $subscription)
                    <flux:table.row wire:key="subscriptions-{{ $subscription->id }}">
                        <flux:table.cell>{{ $subscription->contact?->name ?? $subscription->contact?->fullName() ?? '—' }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$subscription->status->color()">{{ $subscription->status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell>{{ $subscription->plan_name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $subscription->start_date?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $subscription->end_date?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('subscriptions.edit', $subscription)" wire:navigate />
                                @can('delete', $subscription)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $subscription->id }})" />
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
                <flux:button variant="danger" wire:click="deleteSubscription">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
