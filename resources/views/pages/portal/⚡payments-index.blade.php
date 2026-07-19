<?php

use App\Enums\PaymentType;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\ScopesToPortalContact;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.portal')] #[Title('Payments')] class extends Component {
    use ScopesToPortalContact;
    use WithPagination;

    #[Computed]
    public function payments()
    {
        return $this->scopeOwned(Payment::query())
            ->with('invoice')
            ->where('type', PaymentType::Incoming)
            ->latest('payment_date')
            ->paginate(12);
    }

    #[Computed]
    public function remainingBalance(): float
    {
        $contactId = $this->portalContactId();
        $invoiced = (float) Invoice::query()
            ->where('contact_id', $contactId)
            ->whereIn('status', ['sent', 'overdue', 'paid'])
            ->sum('total_amount');
        $paid = (float) Payment::query()
            ->where('contact_id', $contactId)
            ->where('type', PaymentType::Incoming)
            ->sum('amount');

        return max(0, $invoiced - $paid);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <flux:heading size="lg">{{ __('scf.portal.payments') }}</flux:heading>
                <flux:subheading class="mt-1">{{ __('scf.portal.payments_subtitle') }}</flux:subheading>
            </div>
            <div class="rounded-xl bg-amber-500/10 px-4 py-3 text-end">
                <p class="text-xs text-amber-700 dark:text-amber-300">{{ __('scf.portal.remaining_balance') }}</p>
                <p class="text-xl font-semibold tabular-nums text-amber-800 dark:text-amber-200">{{ number_format($this->remainingBalance, 2) }}</p>
            </div>
        </div>
    </div>

    <div class="portal-glass overflow-hidden rounded-2xl">
        <flux:table :paginate="$this->payments">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Method') }}</flux:table.column>
                <flux:table.column>{{ __('Invoice') }}</flux:table.column>
                <flux:table.column>{{ __('Amount') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->payments as $payment)
                    <flux:table.row wire:key="pay-{{ $payment->id }}">
                        <flux:table.cell class="font-medium">{{ $payment->reference_number }}</flux:table.cell>
                        <flux:table.cell>{{ $payment->payment_date?->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>{{ $payment->payment_method ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $payment->invoice?->reference_number ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="tabular-nums font-medium">{{ number_format((float) $payment->amount, 2) }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <x-empty-state icon="banknotes" :title="__('scf.portal.no_payments')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</section>
