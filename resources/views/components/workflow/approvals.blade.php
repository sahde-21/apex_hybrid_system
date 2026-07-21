@props([
    'approvals' => [],
])

<div {{ $attributes->class(['rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900']) }}>
    <flux:heading size="sm" class="mb-3">{{ __('scf.workflow.approvals') }}</flux:heading>

    <ul class="space-y-3">
        @forelse ($approvals as $approval)
            <li wire:key="workflow-approval-{{ $approval->id }}" class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                        {{ __('scf.workflow.level', ['level' => $approval->level]) }}
                        <span class="font-normal text-zinc-500">({{ $approval->level_name }})</span>
                    </p>
                    @if ($approval->user)
                        <p class="text-xs text-zinc-500">
                            {{ __('scf.workflow.acted_by', ['user' => $approval->user->name]) }}
                            @if ($approval->acted_at)
                                · {{ $approval->acted_at->format('Y-m-d H:i') }}
                            @endif
                        </p>
                    @endif
                    @if ($approval->comment)
                        <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">{{ $approval->comment }}</p>
                    @endif
                </div>
                <flux:badge size="sm" :color="$approval->status->color()">{{ $approval->status->label() }}</flux:badge>
            </li>
        @empty
            <li>
                <flux:text class="text-sm text-zinc-500">{{ __('scf.workflow.no_approvals') }}</flux:text>
            </li>
        @endforelse
    </ul>
</div>
