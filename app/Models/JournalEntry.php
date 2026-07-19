<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\JournalEntryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference_number
 * @property Carbon $entry_date
 * @property string $description
 * @property JournalEntryStatus $status
 * @property string $total_debit
 * @property string $total_credit
 * @property string|null $notes
 */
#[Fillable([
    'reference_number',
    'entry_date',
    'fiscal_period_id',
    'branch_id',
    'currency_code',
    'exchange_rate',
    'description',
    'status',
    'total_debit',
    'total_credit',
    'notes',
    'reference_type',
    'reference_id',
    'idempotency_key',
    'created_by',
    'approved_by',
    'posted_by',
    'posted_at',
    'reversed_by',
    'reversed_at',
    'reversal_of_id',
])]
class JournalEntry extends Model
{
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'status' => JournalEntryStatus::class,
            'total_debit' => 'decimal:2',
            'total_credit' => 'decimal:2',
            'exchange_rate' => 'decimal:8',
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<JournalEntryLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class)->orderBy('line_number');
    }

    /**
     * @return BelongsTo<FiscalPeriod, $this>
     */
    public function fiscalPeriod(): BelongsTo
    {
        return $this->belongsTo(FiscalPeriod::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<JournalEntry, $this>
     */
    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    /**
     * @return HasMany<JournalEntry, $this>
     */
    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reversal_of_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function isBalanced(): bool
    {
        return bccomp((string) $this->total_debit, (string) $this->total_credit, 2) === 0;
    }

    public function isEditable(): bool
    {
        return $this->status === JournalEntryStatus::Draft;
    }
}
