@props([
    'name' => null,
])

<flux:dropdown position="bottom" align="start" {{ $attributes }}>
    <flux:sidebar.profile
        :name="$name ?? auth()->user()->name"
        :initials="auth()->user()->initials()"
        icon:trailing="chevrons-up-down"
        data-test="sidebar-menu-button"
        class="rounded-xl ring-1 ring-zinc-200/80 transition hover:bg-zinc-50 dark:ring-zinc-800 dark:hover:bg-zinc-900"
    />

    <flux:menu class="min-w-64">
        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
            <flux:avatar
                :name="auth()->user()->name"
                :initials="auth()->user()->initials()"
            />
            <div class="grid flex-1 text-start text-sm leading-tight">
                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
            </div>
        </div>

        <flux:menu.separator />

        <flux:menu.radio.group>
            <flux:menu.item :href="route('profile.edit')" icon="user-circle" wire:navigate>
                {{ __('scf.settings') }}
            </flux:menu.item>
            @if (Route::has('appearance.edit'))
                <flux:menu.item :href="route('appearance.edit')" icon="swatch" wire:navigate>
                    {{ __('Appearance') }}
                </flux:menu.item>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <flux:menu.item
                    as="button"
                    type="submit"
                    icon="arrow-right-start-on-rectangle"
                    class="w-full cursor-pointer"
                    data-test="logout-button"
                >
                    {{ __('scf.log_out') }}
                </flux:menu.item>
            </form>
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>
