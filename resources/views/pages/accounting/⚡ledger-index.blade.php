<?php

use App\Models\Account;
use App\Services\Accounting\FinancialStatementService;
use App\Services\Accounting\LedgerService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('General Ledger')] class extends Component {
    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    #[Url]
    public ?int $account_id = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('ledgers.read'), 403);
        $this->from = $this->from ?: now()->startOfMonth()->toDateString();
        $this->to = $this->to ?: now()->toDateString();
    }

    #[Computed]
    public function accounts()
    {
        return Account::query()->orderBy('code')->get(['id', 'code', 'name']);
    }

    #[Computed]
    public function rows()
    {
        return app(LedgerService::class)->generalLedger([
            'from' => $this->from,
            'to' => $this->to,
            'account_id' => $this->account_id,
        ]);
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <flux:heading size="xl">{{ __('scf.accounting_engine.ledger_title') }}</flux:heading>
        <div class="mt-4 grid gap-3 md:grid-cols-3">
            <flux:input type="date" wire:model.live="from" :label="__('From')" />
            <flux:input type="date" wire:model.live="to" :label="__('To')" />
            <flux:select wire:model.live="account_id" :label="__('scf.accounting_engine.account')">
                <option value="">{{ __('All') }}</option>
                @foreach ($this->accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <div class="portal-glass overflow-x-auto rounded-2xl">
        <table class="min-w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-4 py-3 text-start">{{ __('Date') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('Reference') }}</th>
                    <th class="px-4 py-3 text-start">{{ __('scf.accounting_engine.account') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('scf.accounting_engine.debit') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('scf.accounting_engine.credit') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('scf.accounting_engine.balance') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->rows as $row)
                    <tr>
                        <td class="px-4 py-3">{{ $row->entry_date }}</td>
                        <td class="px-4 py-3">{{ $row->reference_number }}</td>
                        <td class="px-4 py-3">{{ $row->account_code }} — {{ $row->account_name }}</td>
                        <td class="px-4 py-3 text-end tabular-nums">{{ number_format((float) $row->base_debit, 2) }}</td>
                        <td class="px-4 py-3 text-end tabular-nums">{{ number_format((float) $row->base_credit, 2) }}</td>
                        <td class="px-4 py-3 text-end tabular-nums">{{ number_format((float) $row->running_balance, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-zinc-500">{{ __('No records found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
