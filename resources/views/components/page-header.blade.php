@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->class('scf-page-header') }}>
    <div class="scf-page-header__copy">
        <flux:heading size="xl" class="tracking-tight">{{ $title }}</flux:heading>
        @if ($subtitle)
            <flux:subheading class="max-w-2xl text-pretty">{{ $subtitle }}</flux:subheading>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
