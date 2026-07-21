@props([
    'meta' => [],
])

<div class="flex items-center gap-2 rounded-lg bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-800/50">
    <flux:icon name="paper-clip" class="size-4 text-zinc-500" />
    <div class="min-w-0 flex-1">
        <p class="truncate font-medium text-zinc-800 dark:text-zinc-100">{{ $meta['filename'] ?? __('scf.activity.attachment') }}</p>
        @if (! empty($meta['size']))
            <p class="text-xs text-zinc-500">{{ number_format(((int) $meta['size']) / 1024, 1) }} KB</p>
        @endif
    </div>
</div>
