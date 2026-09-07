<?php

use App\Models\Coupon;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Create Coupons')] class extends \App\Livewire\ConcernBases\CouponValidationRulesBase {
    public string $code = '';
    public string $discount_type = 'percentage';
    public string $discount_value = '0';
    public string $valid_from = '';
    public string $valid_until = '';
    public int $usage_limit = 0;
    public bool $is_active = true;

    public function mount(): void
    {
    }

    public function save(): void
    {
        $validated = $this->validate($this->couponRules());

        Coupon::query()->create($validated);

        Flux::toast(variant: 'success', text: __('Coupons created successfully.'));

        $this->redirect(route('coupons.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Create Coupons') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="code" :label="__('Code')" required />
        <flux:input wire:model="discount_type" :label="__('Discount Type')" />
        <flux:input wire:model="discount_value" type="number" step="0.01" :label="__('Discount Value')" required />
        <flux:input wire:model="valid_from" type="date" :label="__('Valid From')" />
        <flux:input wire:model="valid_until" type="date" :label="__('Valid Until')" />
        <flux:input wire:model="usage_limit" type="number" :label="__('Usage Limit')" />
        <flux:switch wire:model="is_active" :label="__('Is Active')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            <flux:button :href="route('coupons.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
