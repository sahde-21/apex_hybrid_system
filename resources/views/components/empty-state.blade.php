@props([
    'icon' => 'inbox',
    'title' => null,
    'description' => null,
    'actionLabel' => null,
    'actionHref' => null,
])

<div {{ $attributes->class('scf-empty') }}>
    <div class="scf-empty__icon">
        <flux:icon :name="$icon" class="size-6" />
    </div>

    @if ($title)
        <p class="scf-empty__title">{{ $title }}</p>
    @endif

    @if ($description)
        <p class="scf-empty__description">{{ $description }}</p>
    @endif

    {{ $slot }}

    @if ($actionHref && $actionLabel)
        <flux:button :href="$actionHref" variant="primary" size="sm" icon="plus" wire:navigate class="mt-1">
            {{ $actionLabel }}
        </flux:button>
    @endif
</div>
