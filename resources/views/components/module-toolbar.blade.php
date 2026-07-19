@props([
    'exportType' => null,
    'createRoute' => null,
    'createLabel' => null,
    'createPermission' => null,
    'printType' => null,
    'printId' => null,
])

<div {{ $attributes->class('scf-toolbar') }}>
    <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
        {{ $search ?? '' }}
        {{ $filters ?? '' }}
    </div>

    <div class="flex flex-wrap items-center gap-2">
        @if ($exportType)
            @can($exportType.'.export')
                <flux:button :href="route('export.csv', $exportType)" icon="arrow-down-tray" variant="ghost" size="sm">
                    {{ __('Export CSV') }}
                </flux:button>
                <flux:button :href="route('export.excel', $exportType)" icon="table-cells" variant="ghost" size="sm">
                    {{ __('Export Excel') }}
                </flux:button>
            @endcan
        @endif

        @if ($printType && $printId)
            <x-print-button :type="$printType" :id="$printId" />
        @endif

        {{ $actions ?? '' }}

        @if ($createRoute)
            @if ($createPermission === null || auth()->user()?->can($createPermission))
                <flux:button :href="$createRoute" icon="plus" variant="primary" size="sm" wire:navigate>
                    {{ $createLabel ?? __('Add') }}
                </flux:button>
            @endif
        @endif
    </div>
</div>
