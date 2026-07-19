<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\PosShiftStatus;
use Database\Factories\PosShiftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $pos_register_id
 * @property int $user_id
 * @property PosShiftStatus $status
 * @property string $opening_float
 * @property string|null $closing_cash
 * @property string|null $expected_cash
 * @property string|null $cash_difference
 * @property Carbon $opened_at
 * @property Carbon|null $closed_at
 * @property string|null $opening_notes
 * @property string|null $closing_notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'pos_register_id',
    'user_id',
    'status',
    'opening_float',
    'closing_cash',
    'expected_cash',
    'cash_difference',
    'opened_at',
    'closed_at',
    'opening_notes',
    'closing_notes',
    'created_by',
    'updated_by',
])]
class PosShift extends Model
{
    /** @use HasFactory<PosShiftFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PosShiftStatus::class,
            'opening_float' => 'decimal:2',
            'closing_cash' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'cash_difference' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PosRegister, $this>
     */
    public function register(): BelongsTo
    {
        return $this->belongsTo(PosRegister::class, 'pos_register_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<PosSale, $this>
     */
    public function sales(): HasMany
    {
        return $this->hasMany(PosSale::class);
    }

    public function isOpen(): bool
    {
        return $this->status === PosShiftStatus::Open;
    }
}
