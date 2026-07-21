@props([
    'actions' => [],
])

<div {{ $attributes->class(['flex flex-wrap gap-2']) }}>
    @forelse ($actions as $action)
        <flux:button
            size="sm"
            variant="{{ in_array($action['action'], ['reject', 'cancel'], true) ? 'danger' : 'primary' }}"
            wire:click="requestAction('{{ $action['action'] }}')"
            wire:key="workflow-action-{{ $action['action'] }}"
        >
            {{ $action['label'] }}
        </flux:button>
    @empty
        <flux:text class="text-sm text-zinc-500">{{ __('scf.workflow.no_actions') }}</flux:text>
    @endforelse
</div>
