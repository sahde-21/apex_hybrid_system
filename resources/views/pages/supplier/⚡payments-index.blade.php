<?php

use App\Enums\PaymentType;
use App\Models\Payment;
use App\Services\Supplier\SupplierPortalService;
use App\Support\ScopesToSupplierContact;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts.supplier')] #[Title('Payments')] class extends Component {
    use ScopesToSupplierContact;
    use WithPagination;

    #[Computed]
    public function outstanding(): float
    {
        return app(SupplierPortalService::class)->outstandingBalance($this->supplierContactId());
    }

    #[Computed]
    public function payments()
    {
        return $this->scopeOwned(Payment::query())
            ->where('type', PaymentType::Outgoing)
            ->latest('payment_date')
            ->paginate(10);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <flux:heading size="lg">{{ __('scf.supplier_portal.payments') }}</flux:heading>
        <p class="mt-1 text-sm text-zinc-500">{{ __('scf.supplier_portal.payments_subtitle') }}</p>
        <div class="mt-4 portal-kpi inline-block min-w-[220px]">
            <p class="text-sm text-zinc-500">{{ __('scf.supplier_portal.remaining_balance') }}</p>
            <p class="mt-2 text-2xl font-semibold tabular-nums">{{ number_format($this->outstanding, 2) }}</p>
        </div>
    </div>

    <div class="portal-glass overflow-hidden rounded-2xl">
        <flux:table :paginate="$this->payments">
            <flux:table.columns>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Method') }}</flux:table.column>
                <flux:table.column>{{ __('Amount') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->payments as $payment)
                    <flux:table.row wire:key="pay-{{ $payment->id }}">
                        <flux:table.cell class="font-medium">{{ $payment->reference_number }}</flux:table.cell>
                        <flux:table.cell>{{ $payment->payment_date?->format('Y-m-d') }}</flux:table.cell>
                        <flux:table.cell>{{ $payment->payment_method ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="tabular-nums">{{ number_format((float) $payment->amount, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="xs" :href="route('supplier.pdf', ['type' => 'payment', 'id' => $payment->id])" variant="ghost" target="_blank">PDF</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <x-empty-state icon="banknotes" :title="__('scf.supplier_portal.no_payments')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</section>
