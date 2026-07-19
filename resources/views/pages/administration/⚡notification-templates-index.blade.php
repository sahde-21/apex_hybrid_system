<?php

use App\Models\NotificationTemplate;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Notification templates')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $notificationTemplateToDelete = null;

    public bool $showDeleteModal = false;

    #[Computed]
    public function notificationTemplates()
    {
        return NotificationTemplate::query()
            
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhere('name', 'like', "%{$this->search}%")
                        ->orWhere('code', 'like', "%{$this->search}%")
                        ->orWhere('channel', 'like', "%{$this->search}%")
                        ->orWhere('subject', 'like', "%{$this->search}%");
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
        $this->notificationTemplateToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function deleteNotificationTemplate(): void
    {
        if ($this->notificationTemplateToDelete === null) {
            return;
        }

        $model = NotificationTemplate::query()->findOrFail($this->notificationTemplateToDelete);


        $this->authorize('delete', $model);


        $model->delete();

        $this->notificationTemplateToDelete = null;
        $this->showDeleteModal = false;

        Flux::toast(variant: 'success', text: __('Notification templates deleted successfully.'));
    }
}; ?>

<section class="scf-page">
    <x-page-header
        :title="__('Notification templates')"
        :subtitle="__('Manage Notification templates')"
    >
        <x-slot:actions>
            <flux:button :href="route('notification-templates.create')" icon="plus" variant="primary" wire:navigate>
            {{ __('Add') }}
        </flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />

    </div>

    <div class="scf-table-wrap">
        <flux:table :paginate="$this->notificationTemplates">
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Code') }}</flux:table.column>
                <flux:table.column>{{ __('Subject') }}</flux:table.column>
                <flux:table.column>{{ __('Channel') }}</flux:table.column>
                <flux:table.column>{{ __('Body') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->notificationTemplates as $notificationTemplate)
                    <flux:table.row wire:key="notification-templates-{{ $notificationTemplate->id }}">
                        <flux:table.cell>{{ $notificationTemplate->name ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $notificationTemplate->code ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $notificationTemplate->subject ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $notificationTemplate->channel ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $notificationTemplate->body ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" :href="route('notification-templates.edit', $notificationTemplate)" wire:navigate />
                                @can('delete', $notificationTemplate)
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $notificationTemplate->id }})" />
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
                <flux:button variant="danger" wire:click="deleteNotificationTemplate">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
