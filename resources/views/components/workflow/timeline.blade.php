@props([
    'histories' => [],
])

<div {{ $attributes->class(['rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900']) }}>
    <flux:heading size="sm" class="mb-3">{{ __('scf.workflow.timeline') }}</flux:heading>

    <ol class="relative space-y-4 border-s border-zinc-200 ps-4 dark:border-zinc-700">
        @forelse ($histories as $history)
            <li wire:key="workflow-history-{{ $history->id }}" class="relative">
                <span class="absolute -start-[1.3rem] mt-1.5 size-2.5 rounded-full bg-indigo-500 ring-4 ring-white dark:ring-zinc-900"></span>
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                        <span>{{ __('scf.workflow.action_'.$history->action) }}</span>
                        @if ($history->from_status || $history->to_status)
                            <span class="text-xs font-normal text-zinc-500">
                                {{ $history->from_status ?? '—' }} → {{ $history->to_status ?? '—' }}
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-zinc-500">
                        {{ $history->user?->name ?? '—' }}
                        · {{ $history->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                        @if ($history->approval_level)
                            · {{ __('scf.workflow.level', ['level' => $history->approval_level]) }}
                            @if ($history->approval_level_name)
                                ({{ $history->approval_level_name }})
                            @endif
                        @endif
                    </p>
                    @if ($history->comment)
                        <p class="text-sm text-zinc-700 dark:text-zinc-300">{{ $history->comment }}</p>
                    @endif
                </div>
            </li>
        @empty
            <li>
                <flux:text class="text-sm text-zinc-500">{{ __('scf.workflow.no_history') }}</flux:text>
            </li>
        @endforelse
    </ol>
</div>
