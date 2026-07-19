<?php

namespace App\Services\Accounting;

use App\Enums\FiscalPeriodStatus;
use App\Enums\FiscalYearStatus;
use App\Models\FiscalPeriod;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FiscalPeriodService
{
    public function __construct(
        protected AccountingAuditService $audit,
    ) {}

    public function ensureOpenPeriodFor(CarbonInterface $date, bool $allowOverride = false, ?User $user = null): FiscalPeriod
    {
        $period = FiscalPeriod::query()
            ->whereDate('starts_on', '<=', $date->toDateString())
            ->whereDate('ends_on', '>=', $date->toDateString())
            ->first();

        if ($period === null) {
            $period = $this->bootstrapPeriodFor($date);
        }

        if ($period->allowsPosting()) {
            return $period;
        }

        if ($allowOverride && $user?->hasAnyRole(['super-admin', 'owner'])) {
            $this->audit->log('period.override_posting', $period, $user, [
                'date' => $date->toDateString(),
            ]);

            return $period;
        }

        throw ValidationException::withMessages([
            'entry_date' => [__('scf.accounting_engine.period_closed_blocked')],
        ]);
    }

    public function bootstrapPeriodFor(CarbonInterface $date): FiscalPeriod
    {
        return DB::transaction(function () use ($date) {
            $yearStart = $date->copy()->startOfYear();
            $yearEnd = $date->copy()->endOfYear();

            $year = FiscalYear::query()->firstOrCreate(
                [
                    'starts_on' => $yearStart->toDateString(),
                    'ends_on' => $yearEnd->toDateString(),
                ],
                [
                    'name' => (string) $date->year,
                    'status' => FiscalYearStatus::Open,
                ]
            );

            return FiscalPeriod::query()->firstOrCreate(
                [
                    'fiscal_year_id' => $year->id,
                    'period_number' => (int) $date->month,
                ],
                [
                    'name' => $date->format('Y-m'),
                    'starts_on' => $date->copy()->startOfMonth()->toDateString(),
                    'ends_on' => $date->copy()->endOfMonth()->toDateString(),
                    'status' => FiscalPeriodStatus::Open,
                ]
            );
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): FiscalPeriod
    {
        $this->assertCanManage($user);
        $this->assertValidDateRange($data);
        $this->assertNoOverlap((int) $data['fiscal_year_id'], $data['starts_on'], $data['ends_on']);

        $period = FiscalPeriod::query()->create([
            'fiscal_year_id' => $data['fiscal_year_id'],
            'name' => $data['name'],
            'period_number' => $data['period_number'],
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'status' => FiscalPeriodStatus::Open,
        ]);

        $this->audit->log('period.created', $period, $user);

        return $period;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(FiscalPeriod $period, User $user, array $data): FiscalPeriod
    {
        $this->assertCanManage($user);

        if ($period->status !== FiscalPeriodStatus::Open) {
            throw ValidationException::withMessages([
                'period' => [__('scf.accounting_engine.period_must_be_open_to_edit')],
            ]);
        }

        $starts = $data['starts_on'] ?? $period->starts_on->toDateString();
        $ends = $data['ends_on'] ?? $period->ends_on->toDateString();

        $this->assertValidDateRange([
            'starts_on' => $starts,
            'ends_on' => $ends,
        ]);
        $this->assertNoOverlap(
            (int) ($data['fiscal_year_id'] ?? $period->fiscal_year_id),
            $starts,
            $ends,
            $period->id,
        );

        $period->update(collect($data)->only([
            'fiscal_year_id',
            'name',
            'period_number',
            'starts_on',
            'ends_on',
        ])->all());

        $this->audit->log('period.updated', $period, $user);

        return $period->refresh();
    }

    public function delete(FiscalPeriod $period, User $user): void
    {
        $this->assertCanManage($user);

        if ($period->status !== FiscalPeriodStatus::Open) {
            throw ValidationException::withMessages([
                'period' => [__('scf.accounting_engine.period_must_be_open_to_delete')],
            ]);
        }

        if (JournalEntry::query()->where('fiscal_period_id', $period->id)->exists()) {
            throw ValidationException::withMessages([
                'period' => [__('scf.accounting_engine.period_has_journals')],
            ]);
        }

        $this->audit->log('period.deleted', $period, $user, [
            'name' => $period->name,
            'period_number' => $period->period_number,
        ]);

        $period->delete();
    }

    public function closePeriod(FiscalPeriod $period, User $user): FiscalPeriod
    {
        $this->assertCanManage($user);

        if ($period->status !== FiscalPeriodStatus::Open) {
            throw ValidationException::withMessages([
                'period' => [__('scf.accounting_engine.period_must_be_open_to_close')],
            ]);
        }

        $period->update([
            'status' => FiscalPeriodStatus::Closed,
            'closed_by' => $user->id,
            'closed_at' => now(),
        ]);

        $this->audit->log('period.closed', $period, $user);

        return $period->refresh();
    }

    public function lockPeriod(FiscalPeriod $period, User $user): FiscalPeriod
    {
        $this->assertCanManage($user);

        if ($period->status !== FiscalPeriodStatus::Open) {
            throw ValidationException::withMessages([
                'period' => [__('scf.accounting_engine.period_must_be_open_to_lock')],
            ]);
        }

        $period->update([
            'status' => FiscalPeriodStatus::Locked,
            'closed_by' => $user->id,
            'closed_at' => now(),
        ]);

        $this->audit->log('period.locked', $period, $user);

        return $period->refresh();
    }

    public function reopenPeriod(FiscalPeriod $period, User $user): FiscalPeriod
    {
        $this->assertCanManage($user);

        if (! in_array($period->status, [FiscalPeriodStatus::Closed, FiscalPeriodStatus::Locked], true)) {
            throw ValidationException::withMessages([
                'period' => [__('scf.accounting_engine.period_must_be_closed_or_locked_to_reopen')],
            ]);
        }

        $period->update([
            'status' => FiscalPeriodStatus::Open,
            'closed_by' => null,
            'closed_at' => null,
        ]);

        $this->audit->log('period.reopened', $period, $user);

        return $period->refresh();
    }

    public function currentPeriod(?CarbonInterface $date = null): ?FiscalPeriod
    {
        $date ??= now();

        return FiscalPeriod::query()
            ->with('fiscalYear')
            ->whereDate('starts_on', '<=', $date->toDateString())
            ->whereDate('ends_on', '>=', $date->toDateString())
            ->first();
    }

    protected function assertCanManage(User $user): void
    {
        abort_unless(
            $user->can('fiscal-periods.manage') || $user->hasAnyRole(['super-admin', 'owner']),
            403
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertValidDateRange(array $data): void
    {
        if (strtotime((string) $data['ends_on']) < strtotime((string) $data['starts_on'])) {
            throw ValidationException::withMessages([
                'ends_on' => [__('scf.accounting_engine.period_invalid_dates')],
            ]);
        }
    }

    protected function assertNoOverlap(int $fiscalYearId, string $startsOn, string $endsOn, ?int $ignoreId = null): void
    {
        $overlap = FiscalPeriod::query()
            ->where('fiscal_year_id', $fiscalYearId)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereDate('starts_on', '<=', $endsOn)
            ->whereDate('ends_on', '>=', $startsOn)
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'starts_on' => [__('scf.accounting_engine.period_overlap')],
            ]);
        }
    }
}
