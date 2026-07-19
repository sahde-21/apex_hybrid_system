<?php

use App\Models\PortalNotification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.portal')] #[Title('Notifications')] class extends Component {
    use WithPagination;

    #[Computed]
    public function notifications()
    {
        return PortalNotification::query()
            ->where('portal_customer_id', auth('portal')->id())
            ->latest()
            ->paginate(15);
    }

    public function markRead(int $id): void
    {
        $notification = PortalNotification::query()
            ->where('portal_customer_id', auth('portal')->id())
            ->findOrFail($id);
        $notification->markAsRead();
        unset($this->notifications);
    }

    public function markAllRead(): void
    {
        PortalNotification::query()
            ->where('portal_customer_id', auth('portal')->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        unset($this->notifications);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="lg">{{ __('scf.portal.notifications') }}</flux:heading>
            <flux:button size="sm" variant="ghost" wire:click="markAllRead">{{ __('Mark all read') }}</flux:button>
        </div>
    </div>

    <div class="portal-glass rounded-2xl p-5">
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($this->notifications as $notification)
                <div class="flex items-start justify-between gap-3 py-3 {{ $notification->isUnread() ? 'opacity-100' : 'opacity-70' }}">
                    <div class="min-w-0">
                        <p class="font-medium text-sm">{{ $notification->title }}</p>
                        @if ($notification->body)
                            <p class="mt-1 text-sm text-zinc-500">{{ $notification->body }}</p>
                        @endif
                        <p class="mt-1 text-xs text-zinc-400">{{ $notification->created_at?->diffForHumans() }}</p>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        @if ($notification->action_url)
                            <flux:button size="sm" variant="ghost" :href="$notification->action_url" wire:navigate>{{ __('Open') }}</flux:button>
                        @endif
                        @if ($notification->isUnread())
                            <flux:button size="sm" variant="ghost" wire:click="markRead({{ $notification->id }})">{{ __('Read') }}</flux:button>
                        @endif
                    </div>
                </div>
            @empty
                <x-empty-state icon="bell" :title="__('scf.portal.no_notifications')" />
            @endforelse
        </div>
        <div class="mt-4">{{ $this->notifications->links() }}</div>
    </div>
</section>
