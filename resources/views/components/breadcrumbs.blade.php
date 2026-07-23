@props([
    'items' => [],
])

@if (count($items) > 0)
    <nav {{ $attributes->class('scf-breadcrumbs') }} aria-label="{{ __('scf.ui.breadcrumb') }}">
        <ol class="flex flex-wrap items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
            @foreach ($items as $index => $item)
                @php
                    $label = $item['label'] ?? '';
                    $href = $item['href'] ?? null;
                    $isLast = $index === array_key_last($items);
                @endphp
                <li class="flex items-center gap-1.5">
                    @if ($index > 0)
                        <span class="text-zinc-300 dark:text-zinc-600" aria-hidden="true">/</span>
                    @endif
                    @if ($href && ! $isLast)
                        <a href="{{ $href }}" class="scf-focus-ring rounded-sm hover:text-zinc-800 dark:hover:text-zinc-200" wire:navigate>{{ $label }}</a>
                    @else
                        <span @class(['font-medium text-zinc-800 dark:text-zinc-100' => $isLast]) aria-current="{{ $isLast ? 'page' : 'false' }}">{{ $label }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
