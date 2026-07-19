<?php

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Support\ScopesToSupplierContact;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.supplier')] #[Title('Contract')] class extends Component {
    use ScopesToSupplierContact;

    public Contract $contract;

    public function mount(Contract $contract): void
    {
        $this->assertOwns($contract);
        abort_if($contract->status === ContractStatus::Draft, 404);
        $this->contract = $contract;
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="lg">{{ $contract->title }}</flux:heading>
                <p class="mt-1 text-sm text-zinc-500">{{ $contract->reference_number }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:badge :color="$contract->status->color()">{{ $contract->status->label() }}</flux:badge>
                <flux:button size="sm" :href="route('supplier.pdf', ['type' => 'contract', 'id' => $contract->id])" variant="ghost" target="_blank">PDF</flux:button>
            </div>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-xs uppercase text-zinc-500">{{ __('Start') }}</p>
                <p class="mt-1 font-medium">{{ $contract->start_date?->format('Y-m-d') }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">{{ __('Expiration') }}</p>
                <p class="mt-1 font-medium">{{ $contract->end_date?->format('Y-m-d') }}</p>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">{{ __('Renewal status') }}</p>
                <p class="mt-1 font-medium">
                    @if ($contract->status === ContractStatus::Active && $contract->end_date?->isFuture())
                        {{ __('Active — renew before expiry') }}
                    @elseif ($contract->status === ContractStatus::Expired)
                        {{ __('Expired — renewal required') }}
                    @else
                        {{ $contract->status->label() }}
                    @endif
                </p>
            </div>
            <div>
                <p class="text-xs uppercase text-zinc-500">{{ __('Value') }}</p>
                <p class="mt-1 font-medium tabular-nums">{{ number_format((float) $contract->value, 2) }}</p>
            </div>
        </div>
    </div>
</section>
