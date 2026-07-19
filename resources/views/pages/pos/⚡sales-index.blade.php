<?php

use App\Models\PosSale;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('POS Sales')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Computed]
    public function sales()
    {
        return PosSale::query()
            ->with(['contact', 'user', 'register'])
            ->when($this->search, function ($q): void {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term): void {
                    $inner->where('reference_number', 'like', $term)
                        ->orWhereHas('contact', fn ($c) => $c->where('name', 'like', $term));
                });
            })
            ->latest()
            ->paginate(20);
    }
}; ?>

<section class="w-full space-y-6">
    <x-page-header :title="__('scf.pos_sales')" :subtitle="__('View POS transactions, print receipts, and manage refunds.')">
        <x-slot:actions>
            <flux:button variant="primary" :href="route('pos.terminal')" wire:navigate>{{ __('Open POS') }}</flux:button>
        </x-slot:actions>
    </x-page-header>

    <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search by reference or customer…')" class="max-w-md" />

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800">
        <table class="min-w-full text-sm">
            <thead class="bg-zinc-50 text-start dark:bg-zinc-900">
                <tr>
                    <th class="px-3 py-2">{{ __('Reference') }}</th>
                    <th class="px-3 py-2">{{ __('Customer') }}</th>
                    <th class="px-3 py-2">{{ __('Cashier') }}</th>
                    <th class="px-3 py-2">{{ __('Total') }}</th>
                    <th class="px-3 py-2">{{ __('Status') }}</th>
                    <th class="px-3 py-2">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->sales as $sale)
                    <tr class="border-t border-zinc-100 dark:border-zinc-800">
                        <td class="px-3 py-2 font-medium">
                            {{ $sale->reference_number }}
                            @if ($sale->is_return)
                                <span class="ms-1 rounded bg-red-100 px-1.5 py-0.5 text-[10px] text-red-700">{{ __('Return') }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">{{ $sale->contact?->name ?? __('Walk-in') }}</td>
                        <td class="px-3 py-2">{{ $sale->user?->name }}</td>
                        <td class="px-3 py-2">{{ number_format((float) $sale->total_amount, 2) }}</td>
                        <td class="px-3 py-2">{{ $sale->status->label() }}</td>
                        <td class="px-3 py-2">
                            <x-print-button type="pos-sale" :id="$sale->id" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-10 text-center text-zinc-500">{{ __('No POS sales yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $this->sales->links() }}</div>
</section>
