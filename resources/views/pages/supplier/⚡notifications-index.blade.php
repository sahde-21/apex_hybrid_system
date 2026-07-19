<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.supplier')] #[Title('Notifications')] class extends Component {
    use WithPagination;

    public function markRead(int $id): void
    {
        $notification = auth('supplier')->user()->portalNotifications()->findOrFail($id);
        $notification->markAsRead();
    }

    public function markAllRead(): void
    {
        auth('supplier')->user()->portalNotifications()->whereNull('read_at')->update(['read_at' => now()]);
    }

    #[Computed]
    public function notifications()
    {
        return auth('supplier')->user()->portalNotifications()->paginate(15);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="lg">{{ __('scf.supplier_portal.notifications') }}</flux:heading>
        </div>
        <flux:button size="sm" wire:click="markAllRead" variant="ghost">{{ __('Mark all read') }}</flux:button>
    </div>

    <div class="portal-glass rounded-2xl divide-y divide-zinc-100 dark:divide-zinc-800">
        @forelse ($this->notifications as $notification)
            <div class="flex items-start justify-between gap-3 p-4 {{ $notification->read_at ? 'opacity-70' : '' }}" wire:key="n-{{ $notification->id }}">
                <div class="min-w-0">
                    <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $notification->title }}</p>
                    @if ($notification->body)
                        <p class="mt-1 text-sm text-zinc-500">{{ $notification->body }}</p>
                    @endif
                    <p class="mt-1 text-xs text-zinc-400">{{ $notification->created_at?->diffForHumans() }}</p>
                </div>
                <div class="flex shrink-0 gap-1">
                    @if ($notification->action_url)
                        <flux:button size="xs" :href="$notification->action_url" variant="ghost" wire:navigate>{{ __('Open') }}</flux:button>
                    @endif
                    @unless ($notification->read_at)
                        <flux:button size="xs" wire:click="markRead({{ $notification->id }})" variant="ghost">{{ __('Read') }}</flux:button>
                    @endunless
                </div>
            </div>
        @empty
            <div class="p-6">
                <x-empty-state icon="bell" :title="__('scf.supplier_portal.no_notifications')" />
            </div>
        @endforelse
    </div>

    <div>{{ $this->notifications->links() }}</div>
</section>
