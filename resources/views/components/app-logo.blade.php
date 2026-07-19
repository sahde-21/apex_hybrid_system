@props([
    'sidebar' => false,
])

@php
    $brand = config('pwa.short_name', 'SCF');
    $logoClass = 'flex aspect-square size-8 items-center justify-center rounded-lg bg-gradient-to-br from-sky-600 to-sky-800 text-white shadow-sm ring-1 ring-sky-700/20 dark:from-sky-500 dark:to-sky-700';
@endphp

@if ($sidebar)
    <flux:sidebar.brand :name="$brand" {{ $attributes }}>
        <x-slot name="logo" class="{{ $logoClass }}">
            <x-app-logo-icon class="size-5 fill-current text-white" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="$brand" {{ $attributes }}>
        <x-slot name="logo" class="{{ $logoClass }}">
            <x-app-logo-icon class="size-5 fill-current text-white" />
        </x-slot>
    </flux:brand>
@endif
