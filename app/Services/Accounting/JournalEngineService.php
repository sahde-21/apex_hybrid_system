<?php

namespace App\Services\Accounting;

use App\Enums\JournalEntryStatus;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Support\Accounting\JournalLineData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class JournalEngineService
{
    public function __construct(
        protected FiscalPeriodService $periods,
        protected AccountingAuditService $audit,
    ) {}

    /**
     * @param  list<JournalLineData|array<string, mixed>>  $lines
     * @param  array{
     *     reference_number?: string|null,
     *     entry_date: string|\DateTimeInterface,
     *     description: string,
     *     notes?: string|null,
     *     branch_id?: int|null,
     *     currency_code?: string|null,
     *     exchange_rate?: float|int|string|null,
     *     reference?: Model|null,
     *     idempotency_key?: string|null,
     *     auto_post?: bool,
     *     allow_period_override?: bool
     * }  $header
     */
    public function createDraft(User $user, array $header, array $lines): JournalEntry
    {
        $normalized = $this->normalizeLines($lines);
        $this->assertBalanced($normalized);

        return DB::transaction(function () use ($user, $header, $normalized) {
            $entryDate = \Carbon\CarbonImmutable::parse($header['entry_date']);
            $period = $this->periods->ensureOpenPeriodFor(
                $entryDate,
                (bool) ($header['allow_period_override'] ?? false),
                $user
            );

            $currency = (string) ($header['currency_code'] ?? config('accounting.base_currency', 'IQD'));
            $rate = number_format((float) ($header['exchange_rate'] ?? 1), 8, '.', '');

            $totals = $this->sumLines($normalized, $rate);

            $entry = JournalEntry::query()->create([
                'reference_number' => $header['reference_number'] ?? $this->nextReference(),
                'entry_date' => $entryDate->toDateString(),
                'fiscal_period_id' => $period->id,
                'branch_id' => $header['branch_id'] ?? null,
                'currency_code' => $currency,
                'exchange_rate' => $rate,
                'description' => $header['description'],
                'status' => JournalEntryStatus::Draft,
                'total_debit' => $totals['debit'],
                'total_credit' => $totals['credit'],
                'notes' => $header['notes'] ?? null,
                'reference_type' => isset($header['reference']) ? $header['reference']::class : null,
                'reference_id' => isset($header['reference']) ? $header['reference']->getKey() : null,
                'idempotency_key' => $header['idempotency_key'] ?? null,
                'created_by' => $user->id,
            ]);

            $this->syncLines($entry, $normalized, $currency, $rate);
            $this->audit->log('journal.created', $entry, $user);
            $this->flushCaches();

            if ($header['auto_post'] ?? false) {
                return $this->post($entry, $user, (bool) ($header['allow_period_override'] ?? false));
            }

            return $entry->load('lines.account');
        });
    }

    /**
     * @param  list<JournalLineData|array<string, mixed>>  $lines
     * @param  array<string, mixed>  $header
     */
    public function updateDraft(JournalEntry $entry, User $user, array $header, array $lines): JournalEntry
    {
        $this->assertEditable($entry);
        $normalized = $this->normalizeLines($lines);
        $this->assertBalanced($normalized);

        return DB::transaction(function () use ($entry, $user, $header, $normalized) {
            $entryDate = \Carbon\CarbonImmutable::parse($header['entry_date'] ?? $entry->entry_date);
            $period = $this->periods->ensureOpenPeriodFor(
                $entryDate,
                (bool) ($header['allow_period_override'] ?? false),
                $user
            );

            $currency = (string) ($header['currency_code'] ?? $entry->currency_code ?? config('accounting.base_currency', 'IQD'));
            $rate = number_format((float) ($header['exchange_rate'] ?? $entry->exchange_rate ?? 1), 8, '.', '');
            $totals = $this->sumLines($normalized, $rate);

            $entry->update([
                'entry_date' => $entryDate->toDateString(),
                'fiscal_period_id' => $period->id,
                'branch_id' => $header['branch_id'] ?? $entry->branch_id,
                'currency_code' => $currency,
                'exchange_rate' => $rate,
                'description' => $header['description'] ?? $entry->description,
                'notes' => $header['notes'] ?? $entry->notes,
                'total_debit' => $totals['debit'],
                'total_credit' => $totals['credit'],
            ]);

            $entry->lines()->delete();
            $this->syncLines($entry, $normalized, $currency, $rate);
            $this->audit->log('journal.updated', $entry, $user);
            $this->flushCaches();

            return $entry->fresh('lines.account');
        });
    }

    public function post(JournalEntry $entry, User $user, bool $allowPeriodOverride = false): JournalEntry
    {
        abort_unless($user->can('journal-entries.post') || $user->can('journal-entries.approve') || $user->hasAnyRole(['super-admin', 'owner', 'accountant']), 403);

        return DB::transaction(function () use ($entry, $user, $allowPeriodOverride) {
            /** @var JournalEntry $locked */
            $locked = JournalEntry::query()->whereKey($entry->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === JournalEntryStatus::Posted) {
                return $locked->load('lines.account');
            }

            $this->assertEditable($locked);
            $locked->load('lines');
            $this->assertBalanced($locked->lines->map(fn (JournalEntryLine $line) => new JournalLineData(
                (int) $line->account_id,
                (string) $line->debit,
                (string) $line->credit,
            ))->all());

            $this->periods->ensureOpenPeriodFor(
                $locked->entry_date,
                $allowPeriodOverride,
                $user
            );

            $locked->update([
                'status' => JournalEntryStatus::Posted,
                'approved_by' => $user->id,
                'posted_by' => $user->id,
                'posted_at' => now(),
            ]);

            $this->audit->log('journal.posted', $locked, $user);
            $this->flushCaches();

            return $locked->fresh('lines.account');
        });
    }

    public function reverse(JournalEntry $entry, User $user, ?string $reason = null): JournalEntry
    {
        abort_unless($user->can('journal-entries.reverse') || $user->hasAnyRole(['super-admin', 'owner']), 403);

        return DB::transaction(function () use ($entry, $user, $reason) {
            /** @var JournalEntry $locked */
            $locked = JournalEntry::query()->whereKey($entry->id)->lockForUpdate()->with('lines')->firstOrFail();

            if ($locked->status !== JournalEntryStatus::Posted) {
                throw ValidationException::withMessages([
                    'status' => [__('scf.accounting_engine.only_posted_reversible')],
                ]);
            }

            $reversalLines = $locked->lines->map(fn (JournalEntryLine $line) => new JournalLineData(
                accountId: (int) $line->account_id,
                debit: (string) $line->credit,
                credit: (string) $line->debit,
                description: __('scf.accounting_engine.reversal_of', ['ref' => $locked->reference_number]),
                contactId: $line->contact_id,
                branchId: $line->branch_id,
                currencyCode: $line->currency_code,
                exchangeRate: (string) $line->exchange_rate,
            ))->all();

            $reversal = $this->createDraft($user, [
                'entry_date' => now()->toDateString(),
                'description' => __('scf.accounting_engine.reversal_description', ['ref' => $locked->reference_number]),
                'notes' => $reason,
                'branch_id' => $locked->branch_id,
                'currency_code' => $locked->currency_code,
                'exchange_rate' => $locked->exchange_rate,
                'reference' => $locked->reference,
                'idempotency_key' => 'reversal:'.$locked->id.':'.Str::uuid(),
                'auto_post' => true,
                'allow_period_override' => $user->hasAnyRole(['super-admin', 'owner']),
            ], $reversalLines);

            $reversal->update(['reversal_of_id' => $locked->id]);

            $locked->update([
                'status' => JournalEntryStatus::Reversed,
                'reversed_by' => $user->id,
                'reversed_at' => now(),
            ]);

            $this->audit->log('journal.reversed', $locked, $user, [
                'reversal_id' => $reversal->id,
                'reason' => $reason,
            ]);
            $this->flushCaches();

            return $reversal->fresh('lines.account');
        });
    }

    /**
     * @param  list<JournalLineData|array<string, mixed>>  $lines
     * @return list<JournalLineData>
     */
    protected function normalizeLines(array $lines): array
    {
        $normalized = [];

        foreach ($lines as $line) {
            $data = $line instanceof JournalLineData ? $line : JournalLineData::fromArray($line);

            if (bccomp($data->debit, '0', 2) === 0 && bccomp($data->credit, '0', 2) === 0) {
                continue;
            }

            if (bccomp($data->debit, '0', 2) === 1 && bccomp($data->credit, '0', 2) === 1) {
                throw ValidationException::withMessages([
                    'lines' => [__('scf.accounting_engine.line_cannot_have_both')],
                ]);
            }

            if (! Account::query()->whereKey($data->accountId)->where('is_active', true)->exists()) {
                throw ValidationException::withMessages([
                    'lines' => [__('scf.accounting_engine.invalid_account')],
                ]);
            }

            $normalized[] = $data;
        }

        if (count($normalized) < 2) {
            throw ValidationException::withMessages([
                'lines' => [__('scf.accounting_engine.minimum_two_lines')],
            ]);
        }

        return $normalized;
    }

    /**
     * @param  list<JournalLineData>  $lines
     */
    protected function assertBalanced(array $lines): void
    {
        $debit = '0.00';
        $credit = '0.00';

        foreach ($lines as $line) {
            $debit = bcadd($debit, $line->debit, 2);
            $credit = bcadd($credit, $line->credit, 2);
        }

        if (bccomp($debit, $credit, 2) !== 0) {
            throw ValidationException::withMessages([
                'lines' => [__('scf.accounting_engine.unbalanced', ['debit' => $debit, 'credit' => $credit])],
            ]);
        }
    }

    /**
     * @param  list<JournalLineData>  $lines
     * @return array{debit: string, credit: string}
     */
    protected function sumLines(array $lines, string $rate): array
    {
        $debit = '0.00';
        $credit = '0.00';

        foreach ($lines as $line) {
            $lineRate = $line->exchangeRate !== '0' ? $line->exchangeRate : $rate;
            $debit = bcadd($debit, bcmul($line->debit, $lineRate, 8), 2);
            $credit = bcadd($credit, bcmul($line->credit, $lineRate, 8), 2);
        }

        return ['debit' => $debit, 'credit' => $credit];
    }

    /**
     * @param  list<JournalLineData>  $lines
     */
    protected function syncLines(JournalEntry $entry, array $lines, string $currency, string $rate): void
    {
        foreach (array_values($lines) as $index => $line) {
            $lineRate = $line->exchangeRate !== '0' ? $line->exchangeRate : $rate;

            JournalEntryLine::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $line->accountId,
                'line_number' => $index + 1,
                'description' => $line->description,
                'debit' => $line->debit,
                'credit' => $line->credit,
                'currency_code' => $line->currencyCode ?: $currency,
                'exchange_rate' => $lineRate,
                'base_debit' => bcmul($line->debit, $lineRate, 2),
                'base_credit' => bcmul($line->credit, $lineRate, 2),
                'contact_id' => $line->contactId,
                'branch_id' => $line->branchId ?? $entry->branch_id,
            ]);
        }
    }

    protected function assertEditable(JournalEntry $entry): void
    {
        if (! $entry->isEditable()) {
            throw ValidationException::withMessages([
                'status' => [__('scf.accounting_engine.posted_immutable')],
            ]);
        }
    }

    protected function nextReference(): string
    {
        return 'JE-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }

    protected function flushCaches(): void
    {
        Cache::forget('scf:accounting:trial-balance');
        Cache::forget('scf:accounting:coa-tree');
    }
}
