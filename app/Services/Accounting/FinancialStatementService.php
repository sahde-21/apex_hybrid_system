<?php

namespace App\Services\Accounting;

use App\Enums\AccountType;
use App\Enums\JournalEntryStatus;
use App\Models\Account;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;

class FinancialStatementService
{
    public function __construct(
        protected LedgerService $ledgers,
    ) {}

    /**
     * @param  array{from?: string|null, to?: string|null, branch_id?: int|null}  $filters
     * @return array{rows: Collection<int, array<string, mixed>>, total_debit: string, total_credit: string, balanced: bool}
     */
    public function trialBalance(array $filters = []): array
    {
        $rows = $this->ledgers->trialBalance($filters);
        $debit = $rows->reduce(fn ($c, $r) => bcadd($c, $r['debit'], 2), '0.00');
        $credit = $rows->reduce(fn ($c, $r) => bcadd($c, $r['credit'], 2), '0.00');

        return [
            'rows' => $rows,
            'total_debit' => $debit,
            'total_credit' => $credit,
            'balanced' => bccomp($debit, $credit, 2) === 0,
        ];
    }

    /**
     * @param  array{from?: string|null, to?: string|null, branch_id?: int|null}  $filters
     * @return array{revenue: string, cogs: string, gross_profit: string, expenses: string, other_income: string, other_expenses: string, net_profit: string, lines: Collection<int, array<string, mixed>>}
     */
    public function profitAndLoss(array $filters = []): array
    {
        $lines = $this->accountTotals([
            AccountType::Revenue,
            AccountType::CostOfGoodsSold,
            AccountType::Expense,
            AccountType::OtherIncome,
            AccountType::OtherExpense,
        ], $filters);

        $revenue = $this->creditNet($lines, AccountType::Revenue);
        $cogs = $this->debitNet($lines, AccountType::CostOfGoodsSold);
        $expenses = $this->debitNet($lines, AccountType::Expense);
        $otherIncome = $this->creditNet($lines, AccountType::OtherIncome);
        $otherExpenses = $this->debitNet($lines, AccountType::OtherExpense);
        $gross = bcsub($revenue, $cogs, 2);
        $net = bcsub(bcadd($gross, $otherIncome, 2), bcadd($expenses, $otherExpenses, 2), 2);

        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $gross,
            'expenses' => $expenses,
            'other_income' => $otherIncome,
            'other_expenses' => $otherExpenses,
            'net_profit' => $net,
            'lines' => $lines,
        ];
    }

    /**
     * @param  array{as_of?: string|null, branch_id?: int|null}  $filters
     * @return array{assets: string, liabilities: string, equity: string, balanced: bool, lines: Collection<int, array<string, mixed>>}
     */
    public function balanceSheet(array $filters = []): array
    {
        $to = $filters['as_of'] ?? now()->toDateString();
        $lines = $this->accountTotals([
            AccountType::Asset,
            AccountType::Liability,
            AccountType::Equity,
        ], ['to' => $to, 'branch_id' => $filters['branch_id'] ?? null]);

        $assets = $this->debitNet($lines, AccountType::Asset);
        $liabilities = $this->creditNet($lines, AccountType::Liability);
        $equity = $this->creditNet($lines, AccountType::Equity);
        $pnl = $this->profitAndLoss(['to' => $to, 'branch_id' => $filters['branch_id'] ?? null]);
        $equity = bcadd($equity, $pnl['net_profit'], 2);

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'balanced' => bccomp($assets, bcadd($liabilities, $equity, 2), 2) === 0,
            'lines' => $lines,
            'net_profit' => $pnl['net_profit'],
        ];
    }

    /**
     * Simplified cash flow from cash/bank account movements.
     *
     * @param  array{from?: string|null, to?: string|null, branch_id?: int|null}  $filters
     * @return array{inflow: string, outflow: string, net: string}
     */
    public function cashFlow(array $filters = []): array
    {
        $cashIds = Account::query()
            ->whereIn('system_key', [
                config('accounting.system_accounts.cash'),
                config('accounting.system_accounts.bank'),
                config('accounting.system_accounts.card_clearing'),
            ])
            ->pluck('id');

        $totals = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->whereIn('journal_entry_lines.account_id', $cashIds)
            ->where('journal_entries.status', JournalEntryStatus::Posted->value)
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('journal_entries.entry_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('journal_entries.entry_date', '<=', $to))
            ->selectRaw('COALESCE(SUM(base_debit),0) as inflow, COALESCE(SUM(base_credit),0) as outflow')
            ->first();

        $inflow = number_format((float) ($totals->inflow ?? 0), 2, '.', '');
        $outflow = number_format((float) ($totals->outflow ?? 0), 2, '.', '');

        return [
            'inflow' => $inflow,
            'outflow' => $outflow,
            'net' => bcsub($inflow, $outflow, 2),
        ];
    }

    /**
     * @param  list<AccountType>  $types
     * @param  array{from?: string|null, to?: string|null, branch_id?: int|null}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    protected function accountTotals(array $types, array $filters): Collection
    {
        return Account::query()
            ->whereIn('type', array_map(fn (AccountType $t) => $t->value, $types))
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($filters) {
                $totals = JournalEntryLine::query()
                    ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                    ->where('journal_entry_lines.account_id', $account->id)
                    ->where('journal_entries.status', JournalEntryStatus::Posted->value)
                    ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('journal_entries.entry_date', '>=', $from))
                    ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('journal_entries.entry_date', '<=', $to))
                    ->when($filters['branch_id'] ?? null, fn ($q, $id) => $q->where(function ($inner) use ($id) {
                        $inner->where('journal_entry_lines.branch_id', $id)
                            ->orWhere('journal_entries.branch_id', $id);
                    }))
                    ->selectRaw('COALESCE(SUM(base_debit),0) as debit, COALESCE(SUM(base_credit),0) as credit')
                    ->first();

                return [
                    'account_id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'debit' => number_format((float) ($totals->debit ?? 0), 2, '.', ''),
                    'credit' => number_format((float) ($totals->credit ?? 0), 2, '.', ''),
                ];
            })
            ->filter(fn ($row) => bccomp($row['debit'], '0', 2) !== 0 || bccomp($row['credit'], '0', 2) !== 0)
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $lines
     */
    protected function debitNet(Collection $lines, AccountType $type): string
    {
        return $lines
            ->filter(fn ($row) => $row['type'] === $type)
            ->reduce(fn ($c, $r) => bcadd($c, bcsub($r['debit'], $r['credit'], 2), 2), '0.00');
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $lines
     */
    protected function creditNet(Collection $lines, AccountType $type): string
    {
        return $lines
            ->filter(fn ($row) => $row['type'] === $type)
            ->reduce(fn ($c, $r) => bcadd($c, bcsub($r['credit'], $r['debit'], 2), 2), '0.00');
    }
}
