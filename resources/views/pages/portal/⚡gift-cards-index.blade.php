<?php

use App\Models\GiftCard;
use App\Models\Payment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.portal')] #[Title('Gift cards')] class extends \App\Livewire\ConcernBases\ScopesToPortalContactBase {

    #[Computed]
    public function giftCards()
    {
        return GiftCard::query()
            ->where('contact_id', $this->portalContactId())
            ->latest()
            ->get();
    }

    #[Computed]
    public function redeemHistory()
    {
        return Payment::query()
            ->where('contact_id', $this->portalContactId())
            ->whereNotNull('gift_card_id')
            ->with('giftCard')
            ->latest('payment_date')
            ->limit(20)
            ->get();
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <flux:heading size="lg">{{ __('scf.portal.gift_cards') }}</flux:heading>
        <flux:subheading class="mt-1">{{ __('scf.portal.gift_cards_subtitle') }}</flux:subheading>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->giftCards as $card)
            <div class="portal-kpi">
                <div class="flex items-center justify-between gap-2">
                    <p class="font-mono text-sm font-semibold tracking-wide">{{ $card->code }}</p>
                    <flux:badge size="sm" :color="$card->is_active ? 'green' : 'zinc'">
                        {{ $card->is_active ? __('Active') : __('Inactive') }}
                    </flux:badge>
                </div>
                <p class="mt-4 text-xs text-zinc-500">{{ __('scf.portal.remaining_balance') }}</p>
                <p class="text-2xl font-semibold tabular-nums">{{ number_format((float) $card->current_balance, 2) }}</p>
                <p class="mt-2 text-xs text-zinc-500">
                    {{ __('Expires') }}: {{ $card->expires_at?->format('Y-m-d') ?? __('Never') }}
                </p>
            </div>
        @empty
            <div class="portal-glass rounded-2xl p-5 sm:col-span-2 lg:col-span-3">
                <x-empty-state icon="credit-card" :title="__('scf.portal.no_gift_cards')" />
            </div>
        @endforelse
    </div>

    <div class="portal-glass rounded-2xl p-5">
        <flux:heading size="md">{{ __('scf.portal.redeem_history') }}</flux:heading>
        <div class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($this->redeemHistory as $payment)
                <div class="flex items-center justify-between py-2 text-sm">
                    <div>
                        <p class="font-medium">{{ $payment->giftCard?->code ?? $payment->reference_number }}</p>
                        <p class="text-xs text-zinc-500">{{ $payment->payment_date?->format('Y-m-d') }}</p>
                    </div>
                    <span class="tabular-nums">{{ number_format((float) $payment->amount, 2) }}</span>
                </div>
            @empty
                <p class="text-sm text-zinc-500">{{ __('scf.portal.no_redeem_history') }}</p>
            @endforelse
        </div>
    </div>
</section>
