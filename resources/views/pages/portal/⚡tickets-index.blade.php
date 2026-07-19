<?php

use App\Models\Ticket;
use App\Support\ScopesToPortalContact;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.portal')] #[Title('Support')] class extends Component {
    use ScopesToPortalContact;
    use WithPagination;

    #[Url]
    public string $status = '';

    #[Computed]
    public function tickets()
    {
        return $this->scopeOwned(Ticket::query())
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->latest()
            ->paginate(10);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <flux:heading size="lg">{{ __('scf.portal.support') }}</flux:heading>
                <flux:subheading class="mt-1">{{ __('scf.portal.support_subtitle') }}</flux:subheading>
            </div>
            <flux:button :href="route('portal.tickets.create')" variant="primary" icon="plus" size="sm" wire:navigate>
                {{ __('scf.portal.new_ticket') }}
            </flux:button>
        </div>
        <div class="mt-4">
            <flux:select wire:model.live="status" class="w-48">
                <option value="">{{ __('All statuses') }}</option>
                @foreach (['open', 'in_progress', 'resolved', 'closed'] as $status)
                    <option value="{{ $status }}">{{ str_replace('_', ' ', ucfirst($status)) }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="portal-glass overflow-hidden rounded-2xl">
        <flux:table :paginate="$this->tickets">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Subject') }}</flux:table.column>
                <flux:table.column>{{ __('Priority') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->tickets as $ticket)
                    <flux:table.row wire:key="tk-{{ $ticket->id }}">
                        <flux:table.cell class="font-medium">{{ $ticket->reference_number }}</flux:table.cell>
                        <flux:table.cell>{{ $ticket->subject }}</flux:table.cell>
                        <flux:table.cell class="capitalize">{{ $ticket->priority ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$ticket->status->color()">{{ $ticket->status->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="sm" variant="ghost" :href="route('portal.tickets.show', $ticket)" wire:navigate>{{ __('View') }}</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <x-empty-state icon="lifebuoy" :title="__('scf.portal.no_tickets')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</section>
