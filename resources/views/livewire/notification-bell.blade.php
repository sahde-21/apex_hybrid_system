<div class="relative">
    <flux:dropdown position="bottom" align="end">
        <flux:button variant="ghost" size="sm" class="relative" icon="bell" aria-label="{{ __('scf.notification_center.title') }}">
            @if ($this->unreadCount > 0)
                <span class="absolute -end-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-semibold text-white">
                    {{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}
                </span>
            @endif
        </flux:button>

        <flux:menu class="w-[min(100vw-2rem,22rem)] p-0">
            <div class="flex items-center justify-between gap-2 border-b border-zinc-100 px-3 py-2.5 dark:border-zinc-800">
                <flux:heading size="sm">{{ __('scf.notification_center.title') }}</flux:heading>
                @if ($this->unreadCount > 0)
                    <flux:button size="xs" variant="ghost" wire:click="markAllAsRead">{{ __('scf.notification_center.mark_all_read') }}</flux:button>
                @endif
            </div>

            <div class="max-h-80 overflow-y-auto">
                @forelse ($this->recent as $notification)
                    <div
                        wire:key="bell-{{ $notification->id }}"
                        class="border-b border-zinc-50 px-3 py-2.5 last:border-0 dark:border-zinc-800/80 {{ $notification->read_at ? 'opacity-70' : 'bg-sky-50/40 dark:bg-sky-950/20' }}"
                    >
                        <div class="flex items-start gap-2">
                            <flux:icon
                                :name="$notification->category?->icon() ?? 'bell'"
                                class="mt-0.5 size-4 shrink-0 text-zinc-500"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $notification->title() }}</p>
                                @if ($notification->body())
                                    <p class="mt-0.5 line-clamp-2 text-xs text-zinc-500">{{ $notification->body() }}</p>
                                @endif
                                <p class="mt-1 text-[11px] text-zinc-400">{{ $notification->created_at?->diffForHumans() }}</p>
                            </div>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-1">
                            @if ($notification->actionUrl())
                                <flux:button size="xs" :href="$notification->actionUrl()" variant="ghost" wire:navigate>{{ __('Open') }}</flux:button>
                            @endif
                            @unless ($notification->read_at)
                                <flux:button size="xs" wire:click="markAsRead('{{ $notification->id }}')" variant="ghost">{{ __('scf.notification_center.mark_read') }}</flux:button>
                            @endunless
                        </div>
                    </div>
                @empty
                    <div class="px-3 py-6 text-center text-sm text-zinc-500">
                        {{ __('scf.notification_center.empty') }}
                    </div>
                @endforelse
            </div>

            <div class="border-t border-zinc-100 p-2 dark:border-zinc-800">
                <flux:button :href="route('notifications.index')" variant="ghost" size="sm" class="w-full justify-center" wire:navigate>
                    {{ __('scf.notification_center.view_all') }}
                </flux:button>
            </div>
        </flux:menu>
    </flux:dropdown>
</div>
