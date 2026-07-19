<?php

use App\Models\Quotation;
use App\Support\ScopesToPortalContact;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.portal')] #[Title('Quotation')] class extends Component {
    use ScopesToPortalContact;

    public Quotation $quotation;

    public function mount(Quotation $quotation): void
    {
        $this->assertOwns($quotation);
        $this->quotation = $quotation->load('contact');
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="lg">{{ $quotation->reference_number }}</flux:heading>
                <flux:badge class="mt-2" size="sm" :color="$quotation->status->color()">{{ $quotation->status->label() }}</flux:badge>
            </div>
            <div class="flex gap-2">
                <flux:button :href="route('portal.print', ['type' => 'quotation', 'id' => $quotation->id])" icon="printer" size="sm" target="_blank">
                    {{ __('scf.print_a4') }}
                </flux:button>
                <flux:button :href="route('portal.quotations.index')" variant="ghost" size="sm" wire:navigate>{{ __('Back') }}</flux:button>
            </div>
        </div>
        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <div>
                <p class="text-xs text-zinc-500">{{ __('Date') }}</p>
                <p class="mt-1 font-medium">{{ $quotation->quotation_date?->format('Y-m-d') }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-500">{{ __('Valid until') }}</p>
                <p class="mt-1 font-medium">{{ $quotation->valid_until?->format('Y-m-d') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-zinc-500">{{ __('Total') }}</p>
                <p class="mt-1 text-lg font-semibold tabular-nums">{{ number_format((float) $quotation->total_amount, 2) }}</p>
            </div>
        </div>
    </div>
</section>
