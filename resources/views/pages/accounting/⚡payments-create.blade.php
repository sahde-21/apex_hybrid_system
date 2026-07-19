<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use App\Models\Contact;
use App\Models\Invoice;
use App\Services\Sales\PaymentWorkflowService;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create payment')] class extends Component {
    public string $reference_number = '';
    public ?int $contact_id = null;
    public ?int $invoice_id = null;
    public string $payment_date = '';
    public string $amount = '0';
    public string $type = 'incoming';
    public string $payment_method = '';
    public string $account_label = '';
    public string $notes = '';

    public function mount(): void
    {
        $this->payment_date = now()->format('Y-m-d');
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Contact> */
    #[Computed]
    public function contacts()
    {
        return Contact::query()->orderBy('name')->get();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Invoice> */
    #[Computed]
    public function openInvoices()
    {
        return Invoice::query()
            ->whereIn('status', [
                InvoiceStatus::Sent->value,
                InvoiceStatus::PartiallyPaid->value,
                InvoiceStatus::Overdue->value,
            ])
            ->with('contact')
            ->orderBy('invoice_date', 'desc')
            ->limit(200)
            ->get();
    }

    public function updatedInvoiceId($value): void
    {
        if ($value) {
            $invoice = Invoice::find($value);
            if ($invoice) {
                $this->contact_id = $invoice->contact_id;
                $balance = max(0, round((float) $invoice->total_amount - (float) $invoice->paid_amount, 2));
                $this->amount = (string) $balance;
            }
        }
    }

    public function save(): void
    {
        $this->validate([
            'reference_number' => 'required|string|max:255',
            'contact_id' => 'nullable|integer|exists:contacts,id',
            'invoice_id' => 'nullable|integer|exists:invoices,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:incoming,outgoing',
            'payment_method' => 'nullable|string|max:255',
            'account_label' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:5000',
        ]);

        try {
            $payment = app(PaymentWorkflowService::class)->create(auth()->user(), [
                'reference_number' => $this->reference_number,
                'contact_id' => $this->contact_id,
                'invoice_id' => $this->invoice_id,
                'payment_date' => $this->payment_date,
                'amount' => $this->amount,
                'type' => $this->type,
                'payment_method' => $this->payment_method ?: null,
                'account_label' => $this->account_label ?: null,
                'notes' => $this->notes ?: null,
            ]);

            Flux::toast(variant: 'success', text: __('Payment created successfully.'));
            $this->redirect(route('payments.show', $payment), navigate: true);
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());
        }
    }
}; ?>

<section class="scf-page">
    <div class="mb-6 flex items-start justify-between">
        <div>
            <flux:heading size="xl">{{ __('Create payment') }}</flux:heading>
            <flux:subheading>{{ __('Record a new payment transaction') }}</flux:subheading>
        </div>
        <flux:button :href="route('payments.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="scf-card grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <flux:input wire:model="reference_number" :label="__('Reference number')" required />

            <flux:select wire:model="type" :label="__('Type')">
                @foreach (PaymentType::options() as $value => $label)
                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="payment_date" type="date" :label="__('Payment date')" required />

            {{-- Invoice select (for incoming payments) --}}
            @if ($type === 'incoming')
                <flux:select wire:model.live="invoice_id" :label="__('Invoice (open)')" :placeholder="__('None — standalone payment')">
                    <flux:select.option value="">{{ __('None') }}</flux:select.option>
                    @foreach ($this->openInvoices as $inv)
                        <flux:select.option :value="$inv->id">
                            {{ $inv->reference_number }} — {{ $inv->contact?->name ?? '—' }}
                            ({{ __('Balance') }}: {{ number_format(max(0, (float) $inv->total_amount - (float) $inv->paid_amount), 2) }})
                        </flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <flux:select wire:model="contact_id" :label="__('Contact')" :placeholder="__('Select contact')">
                <flux:select.option value="">{{ __('None') }}</flux:select.option>
                @foreach ($this->contacts as $contact)
                    <flux:select.option :value="$contact->id">{{ $contact->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="amount" type="number" step="0.01" min="0.01" :label="__('Amount')" required />
            <flux:input wire:model="payment_method" :label="__('Payment method')" :placeholder="__('cash, bank_transfer, card…')" />
            <flux:input wire:model="account_label" :label="__('Account label')" />
        </div>

        <div class="scf-card">
            <flux:textarea wire:model="notes" :label="__('Notes')" rows="3" />
        </div>

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">{{ __('Create payment') }}</flux:button>
            <flux:button :href="route('payments.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
