@props([
    'title',
    'subtitle' => null,
    'breadcrumbs' => [],
])

<div {{ $attributes->class('scf-page-header') }}>
    <div class="scf-page-header__copy">
        @if (count($breadcrumbs) > 0)
            <x-breadcrumbs :items="$breadcrumbs" class="mb-2" />
        @endif
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
