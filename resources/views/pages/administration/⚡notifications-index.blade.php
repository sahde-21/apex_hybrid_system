<?php

use App\Enums\NotificationCategory;
use App\Enums\NotificationPriority;
use App\Services\Notifications\NotificationCenterService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Notification Center')] class extends Component {
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = 'all';

    #[Url]
    public string $category = '';

    #[Url]
    public string $priority = '';

    #[Url]
    public string $module = '';

    public int $perPage = 15;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function markAsRead(string $id, NotificationCenterService $center): void
    {
        $this->authorize('update', $center->queryFor(auth()->user())->findOrFail($id));
        $center->markAsRead(auth()->user(), $id);
        unset($this->notifications, $this->unreadCount);
        Flux::toast(variant: 'success', text: __('scf.notification_center.marked_read'));
    }

    public function markAllAsRead(NotificationCenterService $center): void
    {
        $center->markAllAsRead(auth()->user());
        unset($this->notifications, $this->unreadCount);
        Flux::toast(variant: 'success', text: __('scf.notification_center.marked_all_read'));
    }

    public function deleteNotification(string $id, NotificationCenterService $center): void
    {
        $notification = $center->queryFor(auth()->user())->findOrFail($id);
        $this->authorize('delete', $notification);
        $center->delete(auth()->user(), $id);
        unset($this->notifications, $this->unreadCount);
        Flux::toast(variant: 'success', text: __('scf.notification_center.deleted'));
    }

    public function loadMore(): void
    {
        $this->perPage += 15;
        unset($this->notifications);
    }

    #[Computed]
    public function unreadCount(): int
    {
        return app(NotificationCenterService::class)->unreadCount(auth()->user());
    }

    #[Computed]
    public function notifications()
    {
        $query = app(NotificationCenterService::class)->queryFor(auth()->user());

        if ($this->search !== '') {
            $query->search($this->search);
        }

        if ($this->status === 'unread') {
            $query->unread();
        } elseif ($this->status === 'read') {
            $query->read();
        }

        if ($this->category !== '') {
            $query->where('category', $this->category);
        }

        if ($this->priority !== '') {
            $query->where('priority', $this->priority);
        }

        if ($this->module !== '') {
            $query->where('module', $this->module);
        }

        return $query->paginate($this->perPage);
    }

    #[Computed]
    public function modules(): array
    {
        return app(NotificationCenterService::class)
            ->queryFor(auth()->user())
            ->whereNotNull('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module')
            ->all();
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('scf.notification_center.title') }}</flux:heading>
                <flux:subheading>{{ __('scf.notification_center.subtitle') }}</flux:subheading>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($this->unreadCount > 0)
                    <flux:badge color="red">{{ $this->unreadCount }} {{ __('scf.notification_center.unread') }}</flux:badge>
                    <flux:button wire:click="markAllAsRead" variant="primary" size="sm">{{ __('scf.notification_center.mark_all_read') }}</flux:button>
                @endif
            </div>
        </div>

        <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search...')" />
            <flux:select wire:model.live="status">
                <option value="all">{{ __('scf.notification_center.filter_all') }}</option>
                <option value="unread">{{ __('scf.notification_center.filter_unread') }}</option>
                <option value="read">{{ __('scf.notification_center.filter_read') }}</option>
            </flux:select>
            <flux:select wire:model.live="category">
                <option value="">{{ __('scf.notification_center.all_categories') }}</option>
                @foreach (NotificationCategory::cases() as $case)
                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="priority">
                <option value="">{{ __('scf.notification_center.all_priorities') }}</option>
                @foreach (NotificationPriority::cases() as $case)
                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="module">
                <option value="">{{ __('scf.notification_center.all_modules') }}</option>
                @foreach ($this->modules as $mod)
                    <option value="{{ $mod }}">{{ $mod }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="portal-glass overflow-hidden rounded-2xl divide-y divide-zinc-100 dark:divide-zinc-800">
        @forelse ($this->notifications as $notification)
            <div
                wire:key="n-{{ $notification->id }}"
                class="flex flex-col gap-3 p-4 sm:flex-row sm:items-start sm:justify-between {{ $notification->read_at ? '' : 'bg-sky-50/50 dark:bg-sky-950/20' }}"
                wire:intersect.once="loadMore"
            >
                <div class="flex min-w-0 flex-1 gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon :name="$notification->category?->icon() ?? 'bell'" class="size-5 text-zinc-600 dark:text-zinc-300" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $notification->title() }}</p>
                            @if ($notification->category)
                                <flux:badge size="sm" :color="$notification->category->color()">{{ $notification->category->label() }}</flux:badge>
                            @endif
                            @if ($notification->priority)
                                <flux:badge size="sm" :color="$notification->priority->color()">{{ $notification->priority->label() }}</flux:badge>
                            @endif
                            @if ($notification->module)
                                <flux:badge size="sm" color="zinc">{{ $notification->module }}</flux:badge>
                            @endif
                        </div>
                        @if ($notification->body())
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $notification->body() }}</p>
                        @endif
                        <p class="mt-1 text-xs text-zinc-400">{{ $notification->created_at?->diffForHumans() }}</p>
                    </div>
                </div>
                <div class="flex shrink-0 flex-wrap gap-1">
                    @if ($notification->actionUrl())
                        <flux:button size="sm" :href="$notification->actionUrl()" variant="ghost" wire:navigate>{{ __('Open') }}</flux:button>
                    @endif
                    @unless ($notification->read_at)
                        <flux:button size="sm" wire:click="markAsRead('{{ $notification->id }}')" variant="ghost">{{ __('scf.notification_center.mark_read') }}</flux:button>
                    @endunless
                    @can('delete', $notification)
                        <flux:button size="sm" wire:click="deleteNotification('{{ $notification->id }}')" variant="danger">{{ __('Delete') }}</flux:button>
                    @endcan
                </div>
            </div>
        @empty
            <div class="p-8">
                <x-empty-state icon="bell" :title="__('scf.notification_center.empty')" :description="__('scf.notification_center.subtitle')" />
            </div>
        @endforelse
    </div>

    @if ($this->notifications->hasMorePages())
        <div class="flex justify-center" wire:intersect="loadMore">
            <flux:button wire:click="loadMore" variant="ghost" wire:loading.attr="disabled">
                {{ __('scf.notification_center.load_more') }}
            </flux:button>
        </div>
    @endif
</section>
