<?php

use App\Enums\BillStatus;
use App\Models\Bill;
use App\Support\ScopesToSupplierContact;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.supplier')] #[Title('Bill')] class extends Component {
    use ScopesToSupplierContact;

    public Bill $bill;

    public function mount(Bill $bill): void
    {
        $this->assertOwns($bill);
        abort_if($bill->status === BillStatus::Draft, 404);
        $this->bill = $bill;
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="lg">{{ $bill->reference_number }}</flux:heading>
                <p class="mt-1 text-sm text-zinc-500">{{ __('scf.supplier_portal.bill_details') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:badge :color="$bill->status->color()">{{ $bill->status->label() }}</flux:badge>
                <flux:button size="sm" :href="route('supplier.print', ['type' => 'bill', 'id' => $bill->id])" variant="ghost" target="_blank" icon="printer">{{ __('Print') }}</flux:button>
                <flux:button size="sm" :href="route('supplier.pdf', ['type' => 'bill', 'id' => $bill->id])" variant="ghost" target="_blank" icon="arrow-down-tray">PDF</flux:button>
            </div>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-xs uppercase text-zinc-500">{{ __('Bill date') }}</p>
                <p class="mt-1 font-medium">{{ $bill->bill_date?->format('Y-m-d') }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">{{ __('Due date') }}</p>
                <p class="mt-1 font-medium">{{ $bill->due_date?->format('Y-m-d') ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">{{ __('Tax') }}</p>
                <p class="mt-1 font-medium tabular-nums">{{ number_format((float) $bill->tax_amount, 2) }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">{{ __('Total') }}</p>
                <p class="mt-1 font-medium tabular-nums">{{ number_format((float) $bill->total_amount, 2) }}</p>
            </div>
        </div>

        <div class="mt-4">
            <p class="text-xs uppercase text-zinc-500">{{ __('Payment status') }}</p>
            <p class="mt-1 font-medium">{{ $bill->status->label() }}</p>
        </div>

        @if ($bill->notes)
            <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">{{ $bill->notes }}</p>
        @endif
    </div>
</section>
