<?php

use App\Models\PosShift;
use App\Services\Pos\PosShiftService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('POS Shifts')] class extends Component {
    use WithPagination;

    #[Computed]
    public function shifts()
    {
        return PosShift::query()
            ->with(['register', 'user'])
            ->latest('opened_at')
            ->paginate(20);
    }
}; ?>

<section class="w-full space-y-6">
    <x-page-header :title="__('scf.pos_shifts')" :subtitle="__('Track register sessions, opening floats, and closing cash.')">
        <x-slot:actions>
            <flux:button variant="primary" :href="route('pos.terminal')" wire:navigate>{{ __('Open POS') }}</flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-800">
        <table class="min-w-full text-sm">
            <thead class="bg-zinc-50 text-start dark:bg-zinc-900">
                <tr>
                    <th class="px-3 py-2">{{ __('Register') }}</th>
                    <th class="px-3 py-2">{{ __('Cashier') }}</th>
                    <th class="px-3 py-2">{{ __('Opened') }}</th>
                    <th class="px-3 py-2">{{ __('Closed') }}</th>
                    <th class="px-3 py-2">{{ __('Opening float') }}</th>
                    <th class="px-3 py-2">{{ __('Expected / Counted') }}</th>
                    <th class="px-3 py-2">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->shifts as $shift)
                    @php($summary = $shift->isOpen() ? app(PosShiftService::class)->summary($shift) : null)
                    <tr class="border-t border-zinc-100 dark:border-zinc-800">
                        <td class="px-3 py-2">{{ $shift->register?->name }}</td>
                        <td class="px-3 py-2">{{ $shift->user?->name }}</td>
                        <td class="px-3 py-2">{{ $shift->opened_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-3 py-2">{{ $shift->closed_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="px-3 py-2">{{ number_format((float) $shift->opening_float, 2) }}</td>
                        <td class="px-3 py-2">
                            @if ($shift->isOpen())
                                {{ number_format((float) $shift->opening_float + (float) ($summary['cash_sales'] ?? 0), 2) }} / —
                            @else
                                {{ number_format((float) $shift->expected_cash, 2) }} / {{ number_format((float) $shift->closing_cash, 2) }}
                                @if ($shift->cash_difference !== null)
                                    <span class="text-xs {{ (float) $shift->cash_difference == 0 ? 'text-emerald-600' : 'text-amber-600' }}">
                                        ({{ number_format((float) $shift->cash_difference, 2) }})
                                    </span>
                                @endif
                            @endif
                        </td>
                        <td class="px-3 py-2">{{ $shift->status->label() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-10 text-center text-zinc-500">{{ __('No shifts yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $this->shifts->links() }}</div>
</section>
