@props([
    'variant' => 'line',
    'lines' => 3,
    'cards' => 4,
])

@if ($variant === 'kpi')
    <div {{ $attributes->class('grid gap-4 sm:grid-cols-2 xl:grid-cols-4') }}>
        @for ($i = 0; $i < $cards; $i++)
            <div class="scf-skeleton-card">
                <div class="scf-skeleton h-3 w-24"></div>
                <div class="scf-skeleton mt-3 h-8 w-32"></div>
            </div>
        @endfor
    </div>
@elseif ($variant === 'table')
    <div {{ $attributes->class('scf-table-wrap p-4 space-y-3') }}>
        <div class="scf-skeleton h-4 w-40"></div>
        @for ($i = 0; $i < $lines; $i++)
            <div class="scf-skeleton h-10 w-full"></div>
        @endfor
    </div>
@elseif ($variant === 'card')
    <div {{ $attributes->class('scf-skeleton-card') }}>
        <div class="scf-skeleton h-4 w-36"></div>
        <div class="scf-skeleton h-3 w-full"></div>
        <div class="scf-skeleton h-3 w-5/6"></div>
        <div class="scf-skeleton h-3 w-2/3"></div>
    </div>
@else
    <div {{ $attributes->class('space-y-2') }}>
        @for ($i = 0; $i < $lines; $i++)
            <div @class([
                'scf-skeleton-line',
                'w-full' => $i % 3 !== 2,
                'w-4/5' => $i % 3 === 2,
            ])></div>
        @endfor
    </div>
@endif
