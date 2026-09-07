<?php

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Services\Portal\PortalService;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;

new #[Layout('layouts.portal')] #[Title('Ticket')] class extends \App\Livewire\ConcernBases\ScopesToPortalContactBase {
    use WithFileUploads;

    public Ticket $ticket;

    public string $replyBody = '';

    /** @var array<int, mixed> */
    public array $attachments = [];

    public function mount(Ticket $ticket): void
    {
        $this->assertOwns($ticket);
        $this->ticket = $ticket->load(['replies.author', 'attachments']);
    }

    public function reply(PortalService $portal): void
    {
        $this->validate([
            'replyBody' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,doc,docx,txt'],
        ]);

        $portal->replyToTicket($this->ticket, auth('portal')->user(), $this->replyBody, $this->attachments);
        $this->reset('replyBody', 'attachments');
        $this->ticket->refresh()->load(['replies.author', 'attachments']);
        Flux::toast(variant: 'success', text: __('scf.portal.reply_sent'));
    }

    public function closeTicket(): void
    {
        $this->assertOwns($this->ticket);
        $this->ticket->update(['status' => TicketStatus::Closed]);
        $this->ticket->refresh();
        Flux::toast(variant: 'success', text: __('scf.portal.ticket_closed'));
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="lg">{{ $ticket->subject }}</flux:heading>
                <p class="mt-1 text-sm text-zinc-500">{{ $ticket->reference_number }}</p>
                <flux:badge class="mt-2" size="sm" :color="$ticket->status->color()">{{ $ticket->status->label() }}</flux:badge>
            </div>
            <div class="flex gap-2">
                @if ($ticket->status !== \App\Enums\TicketStatus::Closed)
                    <flux:button variant="danger" size="sm" wire:click="closeTicket" wire:confirm="{{ __('Close this ticket?') }}">
                        {{ __('Close ticket') }}
                    </flux:button>
                @endif
                <flux:button :href="route('portal.tickets.index')" variant="ghost" size="sm" wire:navigate>{{ __('Back') }}</flux:button>
            </div>
        </div>
        <div class="mt-6 rounded-xl bg-zinc-50 p-4 text-sm dark:bg-zinc-800/50 whitespace-pre-wrap">{{ $ticket->description }}</div>
    </div>

    <div class="portal-glass rounded-2xl p-6">
        <flux:heading size="md">{{ __('Conversation') }}</flux:heading>
        <div class="mt-4 space-y-4">
            @forelse ($ticket->replies->where('is_internal', false) as $reply)
                <div class="rounded-xl border border-zinc-100 p-4 dark:border-zinc-800">
                    <div class="flex items-center justify-between gap-3 text-xs text-zinc-500">
                        <span>{{ $reply->author?->name ?? __('Support') }}</span>
                        <span>{{ $reply->created_at?->diffForHumans() }}</span>
                    </div>
                    <p class="mt-2 whitespace-pre-wrap text-sm">{{ $reply->body }}</p>
                </div>
            @empty
                <p class="text-sm text-zinc-500">{{ __('scf.portal.no_replies') }}</p>
            @endforelse
        </div>

        @if ($ticket->status !== \App\Enums\TicketStatus::Closed)
            <form wire:submit="reply" class="mt-6 space-y-4 border-t border-zinc-100 pt-6 dark:border-zinc-800">
                <flux:textarea wire:model="replyBody" :label="__('Your reply')" rows="4" required />
                <input type="file" wire:model="attachments" multiple class="block w-full text-sm" />
                <flux:button type="submit" variant="primary">{{ __('Send reply') }}</flux:button>
            </form>
        @endif
    </div>
</section>
