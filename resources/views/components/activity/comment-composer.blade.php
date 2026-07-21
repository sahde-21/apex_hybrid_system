@props([
    'canInternal' => false,
    'mentionSuggestions' => [],
    'editing' => false,
    'replying' => false,
])

<form wire:submit="submit" class="space-y-3">
    @if ($editing)
        <flux:text class="text-xs text-amber-600">{{ __('scf.activity.editing_comment') }}</flux:text>
    @elseif ($replying)
        <flux:text class="text-xs text-sky-600">{{ __('scf.activity.replying') }}</flux:text>
    @endif

    <div class="relative">
        <flux:textarea
            wire:model.live.debounce.200ms="body"
            :label="__('scf.activity.comment_label')"
            :placeholder="__('scf.activity.comment_placeholder')"
            rows="3"
        />

        @if (count($mentionSuggestions))
            <div class="absolute z-20 mt-1 w-full rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                @foreach ($mentionSuggestions as $user)
                    <button
                        type="button"
                        wire:key="mention-{{ $user->id }}"
                        wire:click="insertMention(@js($user->name))"
                        class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800"
                    >
                        <span class="font-medium">{{ $user->name }}</span>
                        <span class="text-xs text-zinc-400">{{ $user->email }}</span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    <div class="flex flex-wrap items-center gap-3">
        @if ($canInternal)
            <flux:checkbox wire:model="internal" :label="__('scf.activity.internal_note_toggle')" />
        @endif

        <flux:input type="file" wire:model="attachment" class="max-w-xs" />

        <div class="ms-auto flex gap-2">
            @if ($editing || $replying)
                <flux:button type="button" variant="ghost" wire:click="cancelCompose">{{ __('Cancel') }}</flux:button>
            @endif
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                {{ $editing ? __('scf.activity.save') : __('scf.activity.post') }}
            </flux:button>
        </div>
    </div>
</form>
