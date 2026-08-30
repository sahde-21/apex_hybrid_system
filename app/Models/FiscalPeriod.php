<?php

namespace App\Models;

use App\Enums\FiscalPeriodStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $fiscal_year_id
 * @property string $name
 * @property int $period_number
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property FiscalPeriodStatus $status
 * @property int|null $closed_by
 * @property Carbon|null $closed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read FiscalYear $fiscalYear
 * @property-read User|null $closedBy
 */
#[Fillable([
    'fiscal_year_id',
    'name',
    'period_number',
    'starts_on',
    'ends_on',
    'status',
    'closed_by',
    'closed_at',
])]
class FiscalPeriod extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'closed_at' => 'datetime',
            'status' => FiscalPeriodStatus::class,
            'period_number' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<FiscalYear, $this>
     */
    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function allowsPosting(): bool
    {
        return $this->status->allowsPosting();
    }
}
