@props([
    'entry',
])

@php
    /** @var \App\Support\Activity\TimelineEntry $entry */
@endphp

<article
    wire:key="{{ $entry->id }}"
    class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900"
>
    <div class="flex items-start gap-3">
        <div class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
            @if ($entry->actor?->avatarUrl())
                <img src="{{ $entry->actor->avatarUrl() }}" alt="" class="size-9 rounded-full object-cover" />
            @else
                {{ $entry->actor?->initials() ?? 'SY' }}
            @endif
        </div>

        <div class="min-w-0 flex-1 space-y-2">
            <div class="flex flex-wrap items-center gap-2">
                <flux:icon :name="$entry->type->icon()" class="size-4 text-zinc-500" />
                <flux:badge size="sm" :color="$entry->type->color()">{{ $entry->type->label() }}</flux:badge>
                @if ($entry->visibility === \App\Enums\ActivityVisibility::Internal)
                    <flux:badge size="sm" :color="$entry->visibility->color()">{{ $entry->visibility->label() }}</flux:badge>
                @endif
                @if ($entry->edited)
                    <span class="text-xs text-zinc-400">{{ __('scf.activity.edited') }}</span>
                @endif
                <span class="ms-auto text-xs text-zinc-400">{{ $entry->occurredAt->diffForHumans() }}</span>
            </div>

            <div>
                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $entry->title }}</p>
                <p class="text-xs text-zinc-500">
                    {{ $entry->actor?->name ?? __('scf.activity.system') }}
                    · {{ $entry->occurredAt->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                </p>
            </div>

            @if ($entry->body)
                <p class="whitespace-pre-wrap text-sm text-zinc-700 dark:text-zinc-300">{{ $entry->body }}</p>
            @endif

            @if ($entry->type === \App\Enums\ActivityType::FieldChange && ! empty($entry->meta['changes']))
                <x-activity.field-change-renderer :changes="$entry->meta['changes']" />
            @elseif ($entry->oldValues || $entry->newValues)
                @if (($entry->oldValues['status'] ?? null) || ($entry->newValues['status'] ?? null))
                    <p class="text-xs text-zinc-500">
                        {{ $entry->oldValues['status'] ?? '—' }} → {{ $entry->newValues['status'] ?? '—' }}
                    </p>
                @endif
            @endif

            @if ($entry->hasAttachment)
                <x-activity.attachment-item :meta="$entry->meta" />
            @endif

            @if ($entry->editable || $entry->deletable)
                <div class="flex flex-wrap gap-2 pt-1">
                    @if ($entry->editable && $entry->activityId)
                        <flux:button size="xs" variant="ghost" wire:click="startEdit({{ $entry->activityId }})">
                            {{ __('scf.activity.edit') }}
                        </flux:button>
                    @endif
                    @if ($entry->activityId && $entry->type->isUserGenerated())
                        <flux:button size="xs" variant="ghost" wire:click="setReply({{ $entry->activityId }})">
                            {{ __('scf.activity.reply') }}
                        </flux:button>
                    @endif
                    @if ($entry->deletable && $entry->activityId)
                        <flux:button size="xs" variant="danger" wire:click="deleteActivity({{ $entry->activityId }})" wire:confirm="{{ __('scf.activity.confirm_delete') }}">
                            {{ __('scf.activity.delete') }}
                        </flux:button>
                    @endif
                </div>
            @elseif ($entry->activityId && $entry->type->isUserGenerated())
                <flux:button size="xs" variant="ghost" wire:click="setReply({{ $entry->activityId }})">
                    {{ __('scf.activity.reply') }}
                </flux:button>
            @endif
        </div>
    </div>
</article>
