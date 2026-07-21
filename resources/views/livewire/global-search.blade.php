<div class="relative w-full max-w-md" @keydown.escape.window="$wire.clear()">
    <flux:input
        wire:model.live.debounce.300ms="query"
        icon="magnifying-glass"
        :placeholder="__('scf.performance.global_search_placeholder')"
        autocomplete="off"
        wire:focus="$set('open', true)"
    />

    @if ($open && $this->query !== '')
        <div class="absolute z-50 mt-2 w-full overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
            @forelse ($this->results as $group)
                <div class="border-b border-zinc-100 px-3 py-2 last:border-b-0 dark:border-zinc-800">
                    <div class="mb-1 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                        {{ $group['label'] }}
                    </div>
                    <ul class="space-y-1">
                        @foreach ($group['items'] as $item)
                            <li>
                                <a
                                    href="{{ url($item['url'] ?? '#') }}"
                                    class="block rounded-lg px-2 py-1.5 hover:bg-zinc-50 dark:hover:bg-zinc-800"
                                    wire:navigate
                                    wire:click="clear"
                                >
                                    <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $item['title'] }}</div>
                                    @if ($item['subtitle'])
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $item['subtitle'] }}</div>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <div class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('scf.performance.global_search_empty') }}
                </div>
            @endforelse
        </div>
    @endif
</div>
