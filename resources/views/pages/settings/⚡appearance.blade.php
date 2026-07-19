<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Appearance settings')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Appearance settings') }}</flux:heading>

    <x-pages::settings.layout
        :heading="__('Appearance')"
        :subheading="__('Choose a light, dark, or system theme for SCF Enterprise Suite')"
    >
        <div class="space-y-6">
            <flux:radio.group x-data variant="segmented" x-model="$flux.appearance" class="max-w-md">
                <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
                <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
                <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
            </flux:radio.group>

            <div class="grid gap-3 sm:grid-cols-3">
                <div class="scf-card !bg-zinc-50 !p-4 dark:!bg-zinc-950">
                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('Light') }}</p>
                    <div class="mt-3 space-y-2 rounded-lg border border-zinc-200 bg-white p-3 shadow-sm">
                        <div class="h-2 w-16 rounded bg-sky-600"></div>
                        <div class="h-2 w-full rounded bg-zinc-100"></div>
                        <div class="h-2 w-4/5 rounded bg-zinc-100"></div>
                    </div>
                </div>
                <div class="scf-card !bg-zinc-900 !p-4 !text-zinc-100">
                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-400">{{ __('Dark') }}</p>
                    <div class="mt-3 space-y-2 rounded-lg border border-zinc-700 bg-zinc-950 p-3">
                        <div class="h-2 w-16 rounded bg-sky-400"></div>
                        <div class="h-2 w-full rounded bg-zinc-800"></div>
                        <div class="h-2 w-4/5 rounded bg-zinc-800"></div>
                    </div>
                </div>
                <div class="scf-card !p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('System') }}</p>
                    <div class="mt-3 grid grid-cols-2 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <div class="space-y-2 bg-white p-3">
                            <div class="h-2 w-10 rounded bg-sky-600"></div>
                            <div class="h-2 w-full rounded bg-zinc-100"></div>
                        </div>
                        <div class="space-y-2 bg-zinc-950 p-3">
                            <div class="h-2 w-10 rounded bg-sky-400"></div>
                            <div class="h-2 w-full rounded bg-zinc-800"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-pages::settings.layout>
</section>
