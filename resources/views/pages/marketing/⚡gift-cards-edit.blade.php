<?php

use App\Concerns\GiftCardValidationRules;
use App\Models\GiftCard;
use App\Models\Contact;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Gift cards')] class extends Component {
    use GiftCardValidationRules;
    public GiftCard $giftCard;

    public string $code = '';
    public string $initial_balance = '0';
    public string $current_balance = '0';
    public ?int $contact_id = null;
    public string $expires_at = '';
    public bool $is_active = true;

    public function mount(GiftCard $giftCard): void
    {
        $this->giftCard = $giftCard;
        $this->code = $giftCard->code ?? '';
        $this->initial_balance = (string) $giftCard->initial_balance;
        $this->current_balance = (string) $giftCard->current_balance;
        $this->contact_id = $giftCard->contact_id;
        $this->expires_at = $giftCard->expires_at?->format('Y-m-d') ?? '';
        $this->is_active = $giftCard->is_active;
    }

    #[Computed]
    public function contacts()
    {
        return \App\Models\Contact::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->giftCardUpdateRules($this->giftCard->id));

        $this->giftCard->update($validated);

        Flux::toast(variant: 'success', text: __('Gift cards updated successfully.'));

        $this->redirect(route('gift-cards.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit Gift cards') }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="code" :label="__('Code')" required />
        <flux:input wire:model="initial_balance" type="number" step="0.01" :label="__('Initial Balance')" required />
        <flux:input wire:model="current_balance" type="number" step="0.01" :label="__('Current Balance')" required />
        <flux:select wire:model="contact_id" :label="__('Contact Id')" :placeholder="__('Select')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->contacts as $item)
                <flux:select.option :value="$item->id">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="expires_at" type="date" :label="__('Expires At')" />
        <flux:switch wire:model="is_active" :label="__('Is Active')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('gift-cards.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
