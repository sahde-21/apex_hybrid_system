@props([
    'code' => '500',
    'title' => null,
    'message' => null,
    'icon' => 'exclamation-triangle',
])

@php
    $title = $title ?? __('scf.errors.title_'.$code);
    $message = $message ?? __('scf.errors.message_'.$code);
    $homeRoute = auth()->check() && Route::has('dashboard') ? route('dashboard') : (Route::has('login') ? route('login') : url('/'));
    $homeLabel = auth()->check() ? __('scf.errors.return_dashboard') : __('scf.errors.sign_in');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ckb']) ? 'rtl' : 'ltr' }}">
    <head>
        @include('partials.head')
        <title>{{ $title }} — {{ config('app.name') }}</title>
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <main class="scf-error-page" role="main">
            <a href="#scf-error-content" class="scf-skip-link">{{ __('scf.errors.skip_to_content') }}</a>

            <div id="scf-error-content" class="scf-error-card">
                <a href="{{ $homeRoute }}" class="mb-6 inline-flex items-center gap-2" wire:navigate>
                    <x-app-logo-icon class="size-9 text-sky-700 dark:text-sky-400" />
                    <span class="text-sm font-semibold tracking-tight">{{ config('pwa.name', config('app.name')) }}</span>
                </a>

                <p class="scf-error-code" aria-hidden="true">{{ $code }}</p>

                <flux:icon :name="$icon" class="mx-auto size-12 text-zinc-400 dark:text-zinc-500" />

                <h1 class="mt-4 text-xl font-semibold tracking-tight sm:text-2xl">{{ $title }}</h1>
                <p class="mt-2 max-w-md text-pretty text-sm text-zinc-600 dark:text-zinc-400">{{ $message }}</p>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <flux:button :href="$homeRoute" variant="primary" icon="home" wire:navigate>
                        {{ $homeLabel }}
                    </flux:button>
                    <flux:button type="button" variant="ghost" icon="arrow-uturn-left" onclick="window.history.length > 1 ? window.history.back() : (window.location.href = @js($homeRoute))">
                        {{ __('scf.errors.go_back') }}
                    </flux:button>
                </div>
            </div>
        </main>

        @fluxScripts
    </body>
</html>
