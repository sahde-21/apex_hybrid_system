<flux:dropdown position="bottom" align="end">
    <flux:button
        variant="ghost"
        size="sm"
        icon="language"
        class="rounded-lg text-zinc-600 dark:text-zinc-300"
    >
        <span class="hidden sm:inline">{{ __('scf.language') }}</span>
    </flux:button>
    <flux:menu>
        <flux:menu.item :href="request()->fullUrlWithQuery(['lang' => 'en'])" wire:navigate>English</flux:menu.item>
        <flux:menu.item :href="request()->fullUrlWithQuery(['lang' => 'ckb'])" wire:navigate>کوردی</flux:menu.item>
        <flux:menu.item :href="request()->fullUrlWithQuery(['lang' => 'ar'])" wire:navigate>العربية</flux:menu.item>
    </flux:menu>
</flux:dropdown>
