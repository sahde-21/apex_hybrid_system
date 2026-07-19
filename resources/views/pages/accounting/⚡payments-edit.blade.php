<?php

use App\Concerns\PaymentValidationRules;
use App\Enums\PaymentType;
use App\Models\Contact;
use App\Models\Payment;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit payment')] class extends Component {
    use PaymentValidationRules;

    public Payment $payment;

    public string $reference_number = '';
    public ?int $contact_id = null;
    public string $payment_date = '';
    public string $amount = '0';
    public string $type = 'incoming';
    public string $payment_method = '';
    public string $notes = '';

    public function mount(Payment $payment): void
    {
        $this->payment = $payment;
        $this->reference_number = $payment->reference_number;
        $this->contact_id = $payment->contact_id;
        $this->payment_date = $payment->payment_date->format('Y-m-d');
        $this->amount = (string) $payment->amount;
        $this->type = $payment->type->value;
        $this->payment_method = $payment->payment_method ?? '';
        $this->notes = $payment->notes ?? '';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Contact>
     */
    #[Computed]
    public function contacts()
    {
        return Contact::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $validated = $this->validate($this->paymentRules($this->payment->id));

        $this->payment->update($validated);

        Flux::toast(variant: 'success', text: __('Payment updated successfully.'));

        $this->redirect(route('payments.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Edit payment') }}</flux:heading>
        <flux:subheading>{{ __('Update payment details') }}</flux:subheading>
    </div>

    <form wire:submit="save" class="grid max-w-2xl gap-6">
        <flux:input wire:model="reference_number" :label="__('Reference number')" required />
        <flux:select wire:model="contact_id" :label="__('Contact')" :placeholder="__('Select contact')">
            <flux:select.option value="">{{ __('None') }}</flux:select.option>
            @foreach ($this->contacts as $contact)
                <flux:select.option :value="$contact->id">{{ $contact->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="payment_date" type="date" :label="__('Payment date')" required />
        <flux:input wire:model="amount" type="number" step="0.01" :label="__('Amount')" required />
        <flux:select wire:model="type" :label="__('Type')">
            @foreach (PaymentType::options() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input wire:model="payment_method" :label="__('Payment method')" />
        <flux:textarea wire:model="notes" :label="__('Notes')" />

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('payments.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
