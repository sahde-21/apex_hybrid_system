<?php

use App\Models\Coupon;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

new #[Title('Edit Coupons')] class extends \App\Livewire\ConcernBases\CouponValidationRulesBase {
    public Coupon $coupon;

    public string $code = '';
    public string $discount_type = 'percentage';
    public string $discount_value = '0';
    public string $valid_from = '';
    public string $valid_until = '';
    public int $usage_limit = 0;
    public bool $is_active = true;

    public function mount(Coupon $coupon): void
    {
        $this->coupon = $coupon;
        $this->code = $coupon->code ?? '';
        $this->discount_type = $coupon->discount_type ?? '';
        $this->discount_value = (string) $coupon->discount_value;
        $this->valid_from = $coupon->valid_from?->format('Y-m-d') ?? '';
        $this->valid_until = $coupon->valid_until?->format('Y-m-d') ?? '';
        $this->usage_limit = (string) $coupon->usage_limit;
        $this->is_active = $coupon->is_active;
    }

    public function save(): void
    {
        $validated = $this->validate($this->couponUpdateRules($this->coupon->id));

        $this->coupon->update($validated);

        Flux::toast(variant: 'success', text: __('Coupons updated successfully.'));

        $this->redirect(route('coupons.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit Coupons') }}</flux:heading>
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
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('coupons.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
