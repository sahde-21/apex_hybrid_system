<?php

use App\Models\AuditLog;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('User profile')] class extends Component {
    public User $user;

    public function mount(User $user): void
    {
        $this->authorize('view', $user);
        $this->user = $user->load(['roles', 'permissions']);
    }

    #[Computed]
    public function loginHistory()
    {
        return $this->user->loginHistories()->limit(20)->get();
    }

    #[Computed]
    public function audits()
    {
        return AuditLog::query()
            ->where('auditable_type', $this->user->getMorphClass())
            ->where('auditable_id', $this->user->id)
            ->latest()
            ->limit(20)
            ->get();
    }
}; ?>

<section class="scf-page">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-center gap-4">
            @if ($user->avatarUrl())
                <img src="{{ $user->avatarUrl() }}" alt="" class="size-16 rounded-2xl object-cover">
            @else
                <div class="flex size-16 items-center justify-center rounded-2xl bg-sky-500/15 text-lg font-semibold text-sky-600 dark:text-sky-300">
                    {{ $user->initials() }}
                </div>
            @endif
            <div>
                <flux:heading size="xl">{{ $user->name }}</flux:heading>
                <flux:subheading>{{ $user->email }}</flux:subheading>
                <div class="mt-2 flex flex-wrap gap-2">
                    @if ($user->isLocked())
                        <flux:badge color="amber">{{ __('Locked') }}</flux:badge>
                    @elseif ($user->is_active)
                        <flux:badge color="green">{{ __('Active') }}</flux:badge>
                    @else
                        <flux:badge color="zinc">{{ __('Inactive') }}</flux:badge>
                    @endif
                    @foreach ($user->roles as $role)
                        <flux:badge color="sky">{{ $role->name }}</flux:badge>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('update', $user)
                <flux:button :href="route('users.edit', $user)" icon="pencil-square" variant="primary" wire:navigate>
                    {{ __('Edit user') }}
                </flux:button>
            @endcan
            <flux:button :href="route('users.index')" variant="ghost" wire:navigate>{{ __('Back') }}</flux:button>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="scf-card space-y-3 lg:col-span-1">
            <flux:heading size="lg">{{ __('User profile') }}</flux:heading>
            <div class="space-y-2 text-sm">
                <p><span class="text-zinc-500">{{ __('Phone') }}:</span> {{ $user->phone ?: '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Last login') }}:</span> {{ $user->last_login_at?->toDateTimeString() ?? '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Last login IP') }}:</span> {{ $user->last_login_ip ?: '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Last activity') }}:</span> {{ $user->last_activity_at?->diffForHumans() ?? '—' }}</p>
                <p><span class="text-zinc-500">{{ __('Force password reset') }}:</span> {{ $user->force_password_reset ? __('Yes') : __('No') }}</p>
                @if ($user->locked_reason)
                    <p><span class="text-zinc-500">{{ __('Lock reason') }}:</span> {{ $user->locked_reason }}</p>
                @endif
            </div>

            <div class="pt-2">
                <flux:heading size="sm" class="mb-2">{{ __('Direct permissions') }}</flux:heading>
                <div class="flex flex-wrap gap-1">
                    @forelse ($user->getDirectPermissions() as $permission)
                        <flux:badge size="sm" color="zinc">{{ $permission->name }}</flux:badge>
                    @empty
                        <flux:text class="text-xs">{{ __('None') }}</flux:text>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="scf-card lg:col-span-1">
            <flux:heading size="lg">{{ __('Login history') }}</flux:heading>
            <div class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->loginHistory as $entry)
                    <div class="py-3 text-sm">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-medium capitalize">{{ $entry->event }}</span>
                            <flux:badge size="sm" :color="$entry->successful ? 'green' : 'red'">
                                {{ $entry->successful ? __('Success') : __('Failed') }}
                            </flux:badge>
                        </div>
                        <p class="mt-1 text-xs text-zinc-500">
                            {{ $entry->logged_in_at?->diffForHumans() }} · {{ $entry->ip_address ?: '—' }}
                        </p>
                    </div>
                @empty
                    <p class="py-6 text-sm text-zinc-500">{{ __('No login history yet.') }}</p>
                @endforelse
            </div>
        </div>

        <div class="scf-card lg:col-span-1">
            <flux:heading size="lg">{{ __('Audit logs') }}</flux:heading>
            <div class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->audits as $log)
                    <div class="py-3 text-sm">
                        <p class="font-medium">{{ $log->action }}</p>
                        <p class="mt-1 text-xs text-zinc-500">{{ $log->created_at?->diffForHumans() }}</p>
                    </div>
                @empty
                    <p class="py-6 text-sm text-zinc-500">{{ __('No audit entries yet.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</section>
