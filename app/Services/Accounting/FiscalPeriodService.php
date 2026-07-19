<?php

namespace App\Services\Accounting;

use App\Enums\FiscalPeriodStatus;
use App\Enums\FiscalYearStatus;
use App\Models\FiscalPeriod;
use App\Models\FiscalYear;
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

    public function closePeriod(FiscalPeriod $period, User $user): FiscalPeriod
    {
        abort_unless($user->can('fiscal-periods.manage') || $user->hasAnyRole(['super-admin', 'owner']), 403);

        $period->update([
            'status' => FiscalPeriodStatus::Closed,
            'closed_by' => $user->id,
            'closed_at' => now(),
        ]);

        $this->audit->log('period.closed', $period, $user);

        return $period->refresh();
    }

    public function reopenPeriod(FiscalPeriod $period, User $user): FiscalPeriod
    {
        abort_unless($user->hasAnyRole(['super-admin', 'owner']), 403);

        $period->update([
            'status' => FiscalPeriodStatus::Open,
            'closed_by' => null,
            'closed_at' => null,
        ]);

        $this->audit->log('period.reopened', $period, $user);

        return $period->refresh();
    }
}
