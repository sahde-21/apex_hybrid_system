<?php

use App\Services\Accounting\FinancialStatementService;
use App\Services\Accounting\LedgerService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Financial Statements')] class extends Component {
    #[Url]
    public string $report = 'trial_balance';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('financial-statements.read'), 403);
        $this->from = $this->from ?: now()->startOfYear()->toDateString();
        $this->to = $this->to ?: now()->toDateString();
    }

    #[Computed]
    public function payload(): array
    {
        $service = app(FinancialStatementService::class);
        $filters = ['from' => $this->from, 'to' => $this->to];

        return match ($this->report) {
            'profit_loss' => ['type' => 'profit_loss', 'data' => $service->profitAndLoss($filters)],
            'balance_sheet' => ['type' => 'balance_sheet', 'data' => $service->balanceSheet(['as_of' => $this->to])],
            'cash_flow' => ['type' => 'cash_flow', 'data' => $service->cashFlow($filters)],
            'ar_aging' => ['type' => 'aging', 'data' => app(LedgerService::class)->aging('receivable', ['as_of' => $this->to])],
            'ap_aging' => ['type' => 'aging', 'data' => app(LedgerService::class)->aging('payable', ['as_of' => $this->to])],
            default => ['type' => 'trial_balance', 'data' => $service->trialBalance($filters)],
        };
    }
}; ?>

@php($payload = $this->payload)

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5">
        <flux:heading size="xl">{{ __('scf.accounting_engine.statements_title') }}</flux:heading>
        <flux:subheading>{{ __('scf.accounting_engine.statements_subtitle') }}</flux:subheading>
        <div class="mt-4 grid gap-3 md:grid-cols-4">
            <flux:select wire:model.live="report" :label="__('scf.accounting_engine.report_type')">
                <option value="trial_balance">{{ __('scf.accounting_engine.trial_balance') }}</option>
                <option value="profit_loss">{{ __('scf.accounting_engine.profit_loss') }}</option>
                <option value="balance_sheet">{{ __('scf.accounting_engine.balance_sheet') }}</option>
                <option value="cash_flow">{{ __('scf.accounting_engine.cash_flow') }}</option>
                <option value="ar_aging">{{ __('scf.accounting_engine.ar_aging') }}</option>
                <option value="ap_aging">{{ __('scf.accounting_engine.ap_aging') }}</option>
            </flux:select>
            <flux:input type="date" wire:model.live="from" :label="__('From')" />
            <flux:input type="date" wire:model.live="to" :label="__('To')" />
        </div>
    </div>

    <div class="portal-glass rounded-2xl p-5">
        @if ($payload['type'] === 'trial_balance')
            <div class="mb-4 flex gap-6 text-sm">
                <span>{{ __('scf.accounting_engine.debit') }}: <strong>{{ $payload['data']['total_debit'] }}</strong></span>
                <span>{{ __('scf.accounting_engine.credit') }}: <strong>{{ $payload['data']['total_credit'] }}</strong></span>
                <span class="{{ $payload['data']['balanced'] ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ $payload['data']['balanced'] ? __('scf.accounting_engine.balanced') : __('scf.accounting_engine.unbalanced_short') }}
                </span>
            </div>
            <table class="min-w-full text-sm">
                <thead><tr>
                    <th class="px-3 py-2 text-start">{{ __('Code') }}</th>
                    <th class="px-3 py-2 text-start">{{ __('Name') }}</th>
                    <th class="px-3 py-2 text-end">{{ __('scf.accounting_engine.debit') }}</th>
                    <th class="px-3 py-2 text-end">{{ __('scf.accounting_engine.credit') }}</th>
                </tr></thead>
                <tbody>
                    @foreach ($payload['data']['rows'] as $row)
                        <tr class="border-t border-zinc-100 dark:border-zinc-800">
                            <td class="px-3 py-2 font-mono">{{ $row['code'] }}</td>
                            <td class="px-3 py-2">{{ $row['name'] }}</td>
                            <td class="px-3 py-2 text-end tabular-nums">{{ $row['debit'] }}</td>
                            <td class="px-3 py-2 text-end tabular-nums">{{ $row['credit'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @elseif ($payload['type'] === 'profit_loss')
            <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (['revenue','cogs','gross_profit','expenses','other_income','other_expenses','net_profit'] as $key)
                    <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-900">
                        <dt class="text-xs text-zinc-500">{{ __('scf.accounting_engine.'.$key) }}</dt>
                        <dd class="mt-1 text-xl font-semibold tabular-nums">{{ $payload['data'][$key] }}</dd>
                    </div>
                @endforeach
            </dl>
        @elseif ($payload['type'] === 'balance_sheet')
            <dl class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <dt class="text-xs text-zinc-500">{{ __('scf.accounting_engine.assets') }}</dt>
                    <dd class="mt-1 text-xl font-semibold tabular-nums">{{ $payload['data']['assets'] }}</dd>
                </div>
                <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <dt class="text-xs text-zinc-500">{{ __('scf.accounting_engine.liabilities') }}</dt>
                    <dd class="mt-1 text-xl font-semibold tabular-nums">{{ $payload['data']['liabilities'] }}</dd>
                </div>
                <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <dt class="text-xs text-zinc-500">{{ __('scf.accounting_engine.equity') }}</dt>
                    <dd class="mt-1 text-xl font-semibold tabular-nums">{{ $payload['data']['equity'] }}</dd>
                </div>
            </dl>
        @elseif ($payload['type'] === 'cash_flow')
            <dl class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <dt class="text-xs text-zinc-500">{{ __('scf.accounting_engine.inflow') }}</dt>
                    <dd class="mt-1 text-xl font-semibold tabular-nums">{{ $payload['data']['inflow'] }}</dd>
                </div>
                <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <dt class="text-xs text-zinc-500">{{ __('scf.accounting_engine.outflow') }}</dt>
                    <dd class="mt-1 text-xl font-semibold tabular-nums">{{ $payload['data']['outflow'] }}</dd>
                </div>
                <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-900">
                    <dt class="text-xs text-zinc-500">{{ __('scf.accounting_engine.net') }}</dt>
                    <dd class="mt-1 text-xl font-semibold tabular-nums">{{ $payload['data']['net'] }}</dd>
                </div>
            </dl>
        @else
            <table class="min-w-full text-sm">
                <thead><tr>
                    <th class="px-3 py-2 text-start">{{ __('Contact') }}</th>
                    <th class="px-3 py-2 text-end">{{ __('Total') }}</th>
                </tr></thead>
                <tbody>
                    @forelse ($payload['data'] as $row)
                        <tr class="border-t border-zinc-100 dark:border-zinc-800">
                            <td class="px-3 py-2">{{ $row->contact_name }}</td>
                            <td class="px-3 py-2 text-end tabular-nums">{{ $row->total }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-3 py-8 text-center text-zinc-500">{{ __('No records found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif
    </div>
</section>
