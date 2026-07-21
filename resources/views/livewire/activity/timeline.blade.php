<div class="space-y-4" dir="auto" wire:key="activity-timeline-{{ $subjectType }}-{{ $subjectId }}">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <flux:heading size="lg">{{ __('scf.activity.timeline_title') }}</flux:heading>
        <flux:text class="text-xs text-zinc-500">{{ __('scf.activity.timeline_subtitle') }}</flux:text>
    </div>

    @if ($this->canComment())
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <x-activity.comment-composer
                :can-internal="$this->canInternalNote()"
                :mention-suggestions="$this->mentionSuggestions"
                :editing="$editingId !== null"
                :replying="$replyToId !== null"
            />
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($this->entries as $entry)
            <x-activity.timeline-item :entry="$entry" />
        @empty
            <x-empty-state icon="chat-bubble-left-right" :title="__('scf.activity.empty_timeline')" />
        @endforelse
    </div>

    @if ($this->entries->hasMorePages())
        <div class="flex justify-center">
            <flux:button variant="ghost" wire:click="loadMore" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="loadMore">{{ __('scf.activity.load_more') }}</span>
                <span wire:loading wire:target="loadMore">{{ __('scf.activity.loading') }}</span>
            </flux:button>
        </div>
    @endif
</div>
