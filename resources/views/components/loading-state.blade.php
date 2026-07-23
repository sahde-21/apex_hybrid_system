@props([
    'target' => null,
    'variant' => 'overlay',
])

@if ($variant === 'inline')
    <div
        {{ $attributes->class('flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400') }}
        @if ($target) wire:loading wire:target="{{ $target }}" @else wire:loading @endif
    >
        <flux:icon name="arrow-path" class="size-4 animate-spin" />
        <span>{{ trim((string) $slot) !== '' ? $slot : __('scf.ui.loading') }}</span>
    </div>
@else
    <div
        {{ $attributes->class('scf-loading-overlay') }}
        @if ($target) wire:loading.delay.shortest wire:target="{{ $target }}" @else wire:loading.delay.shortest @endif
        aria-live="polite"
        aria-busy="true"
    >
        <div class="scf-loading-overlay__panel">
            <flux:icon name="arrow-path" class="size-6 animate-spin text-sky-600 dark:text-sky-400" />
            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                {{ $slot->isEmpty() ? __('scf.ui.loading') : $slot }}
            </span>
        </div>
    </div>
@endif
