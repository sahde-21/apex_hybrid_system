@props([
    'changes' => [],
])

<div {{ $attributes->class(['space-y-2']) }}>
    @forelse ($changes as $field => $change)
        <div class="grid grid-cols-1 gap-1 rounded-lg border border-zinc-100 p-2 text-xs dark:border-zinc-800 sm:grid-cols-3">
            <div class="font-medium text-zinc-700 dark:text-zinc-200">{{ is_string($field) ? \Illuminate\Support\Str::headline((string) $field) : ($change['label'] ?? $field) }}</div>
            <div class="text-zinc-500">
                <span class="text-[10px] uppercase tracking-wide">{{ __('scf.activity.previous') }}</span>
                <div class="font-mono">{{ is_scalar($change['old'] ?? null) || ($change['old'] ?? null) === null ? ($change['old'] ?? '—') : json_encode($change['old']) }}</div>
            </div>
            <div class="text-zinc-800 dark:text-zinc-100">
                <span class="text-[10px] uppercase tracking-wide">{{ __('scf.activity.new') }}</span>
                <div class="font-mono">{{ is_scalar($change['new'] ?? null) || ($change['new'] ?? null) === null ? ($change['new'] ?? '—') : json_encode($change['new']) }}</div>
            </div>
        </div>
    @empty
        <flux:text class="text-sm text-zinc-500">{{ __('scf.activity.no_field_changes') }}</flux:text>
    @endforelse
</div>
