<?php

use App\Enums\BillStatus;
use App\Enums\ContractStatus;
use App\Enums\PaymentType;
use App\Enums\PurchaseOrderStatus;
use App\Models\Bill;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('layouts.supplier')] #[Title('Documents')] class extends \App\Livewire\ConcernBases\ScopesToSupplierContactBase {

    #[Computed]
    public function documents(): array
    {
        $contactId = $this->supplierContactId();

        $orders = PurchaseOrder::query()
            ->where('contact_id', $contactId)
            ->where('status', '!=', PurchaseOrderStatus::Draft)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (PurchaseOrder $o) => [
                'type' => 'purchase-order',
                'label' => __('Purchase Order'),
                'reference' => $o->reference_number,
                'date' => $o->order_date,
                'id' => $o->id,
            ]);

        $bills = Bill::query()
            ->where('contact_id', $contactId)
            ->where('status', '!=', BillStatus::Draft)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (Bill $b) => [
                'type' => 'bill',
                'label' => __('Bill'),
                'reference' => $b->reference_number,
                'date' => $b->bill_date,
                'id' => $b->id,
            ]);

        $contracts = Contract::query()
            ->where('contact_id', $contactId)
            ->where('status', '!=', ContractStatus::Draft)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (Contract $c) => [
                'type' => 'contract',
                'label' => __('Contract'),
                'reference' => $c->reference_number,
                'date' => $c->start_date,
                'id' => $c->id,
            ]);

        $payments = Payment::query()
            ->where('contact_id', $contactId)
            ->where('type', PaymentType::Outgoing)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (Payment $p) => [
                'type' => 'payment',
                'label' => __('Receipt'),
                'reference' => $p->reference_number,
                'date' => $p->payment_date,
                'id' => $p->id,
            ]);

        return $orders->concat($bills)->concat($contracts)->concat($payments)
            ->sortByDesc(fn ($d) => $d['date']?->timestamp ?? 0)
            ->values()
            ->all();
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <flux:heading size="lg">{{ __('scf.supplier_portal.documents') }}</flux:heading>
        <p class="mt-1 text-sm text-zinc-500">{{ __('scf.supplier_portal.documents_subtitle') }}</p>
    </div>

    <div class="portal-glass overflow-hidden rounded-2xl">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Type') }}</flux:table.column>
                <flux:table.column>{{ __('Reference') }}</flux:table.column>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->documents as $doc)
                    <flux:table.row wire:key="doc-{{ $doc['type'] }}-{{ $doc['id'] }}">
                        <flux:table.cell>{{ $doc['label'] }}</flux:table.cell>
                        <flux:table.cell class="font-medium">{{ $doc['reference'] }}</flux:table.cell>
                        <flux:table.cell>{{ $doc['date']?->format('Y-m-d') ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-1">
                                <flux:button size="xs" :href="route('supplier.pdf', ['type' => $doc['type'], 'id' => $doc['id']])" variant="ghost" target="_blank">PDF</flux:button>
                                <flux:button size="xs" :href="route('supplier.print', ['type' => $doc['type'], 'id' => $doc['id']])" variant="ghost" target="_blank">{{ __('Print') }}</flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4">
                            <x-empty-state icon="folder" :title="__('scf.supplier_portal.no_documents')" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</section>
