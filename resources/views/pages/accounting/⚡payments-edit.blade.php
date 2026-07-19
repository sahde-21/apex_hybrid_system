<?php

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Contact;
use App\Models\Payment;
use App\Services\Sales\PaymentWorkflowService;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit payment')] class extends Component {
    public Payment $payment;

    public string $reference_number = '';
    public ?int $contact_id = null;
    public string $payment_date = '';
    public string $amount = '0';
    public string $type = 'incoming';
    public string $payment_method = '';
    public string $account_label = '';
    public string $notes = '';

    public function mount(Payment $payment): void
    {
        $this->authorize('update', $payment);

        if ($payment->status !== PaymentStatus::Draft) {
            $this->redirect(route('payments.show', $payment), navigate: true);
            return;
        }

        $this->payment = $payment->load('contact', 'invoice');
        $this->reference_number = $payment->reference_number;
        $this->contact_id = $payment->contact_id;
        $this->payment_date = $payment->payment_date->format('Y-m-d');
        $this->amount = (string) $payment->amount;
        $this->type = $payment->type->value;
        $this->payment_method = $payment->payment_method ?? '';
        $this->account_label = $payment->account_label ?? '';
        $this->notes = $payment->notes ?? '';
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Contact> */
    #[Computed]
    public function contacts()
    {
        return Contact::query()->orderBy('name')->get();
    }

    public function save(): void
    {
        $this->validate([
            'reference_number' => 'required|string|max:255',
            'contact_id' => 'nullable|integer|exists:contacts,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:incoming,outgoing',
            'payment_method' => 'nullable|string|max:255',
            'account_label' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:5000',
        ]);

        try {
            $this->payment->update([
                'reference_number' => $this->reference_number,
                'contact_id' => $this->contact_id,
                'payment_date' => $this->payment_date,
                'amount' => $this->amount,
                'type' => $this->type,
                'payment_method' => $this->payment_method ?: null,
                'account_label' => $this->account_label ?: null,
                'notes' => $this->notes ?: null,
            ]);

            Flux::toast(variant: 'success', text: __('Payment updated successfully.'));
            $this->redirect(route('payments.show', $this->payment), navigate: true);
        } catch (\Exception $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }
}; ?>

<section class="scf-page">
    <div class="mb-6 flex items-start justify-between">
        <div>
            <flux:heading size="xl">{{ __('Edit payment') }}</flux:heading>
            <flux:subheading>{{ $payment->reference_number }}</flux:subheading>
        </div>
        <flux:button :href="route('payments.show', $payment)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
    </div>

    @if ($payment->invoice)
        <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200">
            {{ __('This payment is linked to invoice') }}
            <strong>{{ $payment->invoice->reference_number }}</strong>.
            {{ __('Invoice linkage cannot be changed after creation.') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div class="scf-card grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <flux:input wire:model="reference_number" :label="__('Reference number')" required />
            <flux:select wire:model="type" :label="__('Type')">
                @foreach (PaymentType::options() as $value => $label)
                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="payment_date" type="date" :label="__('Payment date')" required />
            <flux:select wire:model="contact_id" :label="__('Contact')" :placeholder="__('Select contact')">
                <flux:select.option value="">{{ __('None') }}</flux:select.option>
                @foreach ($this->contacts as $contact)
                    <flux:select.option :value="$contact->id">{{ $contact->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="amount" type="number" step="0.01" min="0.01" :label="__('Amount')" required />
            <flux:input wire:model="payment_method" :label="__('Payment method')" />
            <flux:input wire:model="account_label" :label="__('Account label')" />
        </div>

        <div class="scf-card">
            <flux:textarea wire:model="notes" :label="__('Notes')" rows="3" />
        </div>

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Save changes') }}</flux:button>
            <flux:button :href="route('payments.show', $payment)" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
