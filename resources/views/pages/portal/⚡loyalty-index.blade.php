<?php

use App\Models\LoyaltyBalance;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyRedemption;
use App\Services\Portal\PortalService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.portal')] #[Title('Loyalty')] class extends \App\Livewire\ConcernBases\ScopesToPortalContactBase {

    public string $rewardLabel = '';

    public float $redeemPoints = 100;

    public ?int $programId = null;

    #[Computed]
    public function balances()
    {
        return LoyaltyBalance::query()
            ->with('program')
            ->where('contact_id', $this->portalContactId())
            ->get();
    }

    #[Computed]
    public function programs()
    {
        return LoyaltyProgram::query()->where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function history()
    {
        return LoyaltyRedemption::query()
            ->with('program')
            ->where('contact_id', $this->portalContactId())
            ->latest()
            ->limit(20)
            ->get();
    }

    public function redeem(PortalService $portal): void
    {
        $this->validate([
            'programId' => ['required', 'integer', 'exists:loyalty_programs,id'],
            'redeemPoints' => ['required', 'numeric', 'min:1'],
            'rewardLabel' => ['required', 'string', 'max:255'],
        ]);

        $program = LoyaltyProgram::query()->where('is_active', true)->findOrFail($this->programId);
        $portal->redeemLoyalty(auth('portal')->user(), $program, (float) $this->redeemPoints, $this->rewardLabel);

        $this->reset('rewardLabel', 'redeemPoints');
        unset($this->balances, $this->history);
        Flux::toast(variant: 'success', text: __('scf.portal.loyalty_redeemed_title'));
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <flux:heading size="lg">{{ __('scf.portal.loyalty') }}</flux:heading>
        <flux:subheading class="mt-1">{{ __('scf.portal.loyalty_subtitle') }}</flux:subheading>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->balances as $balance)
            <div class="portal-kpi">
                <p class="text-sm text-zinc-500">{{ $balance->program?->name }}</p>
                <p class="mt-2 text-3xl font-semibold tabular-nums">{{ number_format((float) $balance->points, 0) }}</p>
                <p class="mt-1 text-xs text-zinc-500">{{ __('scf.portal.points') }}</p>
            </div>
        @empty
            <div class="portal-glass rounded-2xl p-5 sm:col-span-2 lg:col-span-3">
                <x-empty-state icon="gift" :title="__('scf.portal.no_loyalty')" />
            </div>
        @endforelse
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="portal-glass rounded-2xl p-5">
            <flux:heading size="md">{{ __('scf.portal.redeem_reward') }}</flux:heading>
            <form wire:submit="redeem" class="mt-4 space-y-4">
                <flux:select wire:model="programId" :label="__('Program')">
                    <option value="">{{ __('Select...') }}</option>
                    @foreach ($this->programs as $program)
                        <option value="{{ $program->id }}">{{ $program->name }}</option>
                    @endforeach
                </flux:select>
                <flux:input type="number" step="1" min="1" wire:model="redeemPoints" :label="__('scf.portal.points')" />
                <flux:input wire:model="rewardLabel" :label="__('scf.portal.reward_label')" />
                <flux:button type="submit" variant="primary">{{ __('Redeem') }}</flux:button>
            </form>
        </div>

        <div class="portal-glass rounded-2xl p-5">
            <flux:heading size="md">{{ __('scf.portal.loyalty_history') }}</flux:heading>
            <div class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->history as $item)
                    <div class="flex items-center justify-between py-2 text-sm">
                        <div>
                            <p class="font-medium">{{ $item->reward_label }}</p>
                            <p class="text-xs text-zinc-500">{{ $item->created_at?->diffForHumans() }}</p>
                        </div>
                        <span class="tabular-nums text-rose-600">-{{ number_format((float) $item->points, 0) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">{{ __('scf.portal.no_redemptions') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</section>
