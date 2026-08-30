<?php

namespace App\Services\Accounting;

use App\Enums\JournalEntryStatus;
use App\Models\Account;
use App\Models\Contact;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /**
     * @param  array{from?: string|null, to?: string|null, account_id?: int|null, branch_id?: int|null, contact_id?: int|null, currency_code?: string|null}  $filters
     * @return Collection<int, object>
     */
    public function generalLedger(array $filters = []): Collection
    {
        $query = JournalEntryLine::query()
            ->select([
                'journal_entry_lines.*',
                'journal_entries.entry_date',
                'journal_entries.reference_number',
                'journal_entries.status',
                'accounts.code as account_code',
                'accounts.name as account_name',
            ])
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.status', JournalEntryStatus::Posted->value)
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('journal_entries.entry_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('journal_entries.entry_date', '<=', $to))
            ->when($filters['account_id'] ?? null, fn ($q, $id) => $q->where('journal_entry_lines.account_id', $id))
            ->when($filters['branch_id'] ?? null, fn ($q, $id) => $q->where(function ($inner) use ($id) {
                $inner->where('journal_entry_lines.branch_id', $id)
                    ->orWhere('journal_entries.branch_id', $id);
            }))
            ->when($filters['contact_id'] ?? null, fn ($q, $id) => $q->where('journal_entry_lines.contact_id', $id))
            ->when($filters['currency_code'] ?? null, fn ($q, $code) => $q->where('journal_entry_lines.currency_code', $code))
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entry_lines.id');

        $rows = $query->get();
        $running = '0.00';

        return $rows->map(function ($row) use (&$running) {
            $running = bcadd($running, bcsub(
                number_format((float) $row->base_debit, 2, '.', ''),
                number_format((float) $row->base_credit, 2, '.', ''),
                2
            ), 2);
            $row->running_balance = $running;

            return $row;
        });
    }

    /**
     * @param  array{from?: string|null, to?: string|null, branch_id?: int|null}  $filters
     * @return Collection<int, array{account_id: int, code: string, name: string, type: 'asset'|'cogs'|'equity'|'expense'|'liability'|'other_expense'|'other_income'|'revenue', debit: numeric-string, credit: numeric-string, raw_debit: numeric-string, raw_credit: numeric-string}>
     */
    public function trialBalance(array $filters = []): Collection
    {
        $rows = Account::query()
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

                $debit = number_format((float) ($totals->debit ?? 0), 2, '.', '');
                $credit = number_format((float) ($totals->credit ?? 0), 2, '.', '');
                $net = bcsub($debit, $credit, 2);

                return [
                    'account_id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type->value,
                    'debit' => bccomp($net, '0', 2) === 1 ? $net : '0.00',
                    'credit' => bccomp($net, '0', 2) === -1 ? bcmul($net, '-1', 2) : '0.00',
                    'raw_debit' => $debit,
                    'raw_credit' => $credit,
                ];
            })
            ->filter(fn (array $row) => bccomp($row['raw_debit'], '0', 2) !== 0 || bccomp($row['raw_credit'], '0', 2) !== 0)
            ->values();

        return $rows;
    }

    /**
     * @param  array{as_of?: string|null, branch_id?: int|null}  $filters
     * @return Collection<int, \stdClass>
     */
    public function aging(string $side, array $filters = []): Collection
    {
        $accountKey = $side === 'payable' ? 'accounts_payable' : 'accounts_receivable';
        $account = app(ChartOfAccountsService::class)->findSystem(config('accounting.system_accounts.'.$accountKey));

        $asOf = $filters['as_of'] ?? now()->toDateString();

        $rows = JournalEntryLine::query()
            ->select([
                'journal_entry_lines.contact_id',
                DB::raw('COALESCE(SUM(base_debit - base_credit),0) as balance'),
            ])
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entry_lines.account_id', $account->id)
            ->where('journal_entries.status', JournalEntryStatus::Posted->value)
            ->whereDate('journal_entries.entry_date', '<=', $asOf)
            ->whereNotNull('journal_entry_lines.contact_id')
            ->groupBy('journal_entry_lines.contact_id')
            ->havingRaw('ABS(COALESCE(SUM(base_debit - base_credit),0)) > 0.009')
            ->get();

        $contacts = Contact::query()
            ->whereIn('id', $rows->pluck('contact_id'))
            ->pluck('name', 'id');

        return $rows->map(function ($row) use ($side, $contacts) {
            $balance = number_format((float) $row->balance, 2, '.', '');
            if ($side === 'payable') {
                $balance = bcmul($balance, '-1', 2);
            }

            $zero = number_format(0, 2, '.', '');

            $entry = new \stdClass;
            $entry->contact_id = (int) $row->contact_id;
            $entry->contact_name = (string) ($contacts[$row->contact_id] ?? '#'.$row->contact_id);
            $entry->current = $balance;
            $entry->days_1_30 = $zero;
            $entry->days_31_60 = $zero;
            $entry->days_61_90 = $zero;
            $entry->days_90_plus = $zero;
            $entry->total = $balance;

            return $entry;
        });
    }
}
