<?php

namespace App\Models;

use App\Concerns\Auditable;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string|null $discount_type
 * @property string $discount_value
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_until
 * @property int|null $usage_limit
 * @property int $usage_count
 * @property bool $is_active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'code',
    'discount_type',
    'discount_value',
    'valid_from',
    'valid_until',
    'usage_limit',
    'usage_count',
    'is_active',
    'created_by',
    'updated_by',
])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<PosSale, $this>
     */
    public function posSales(): HasMany
    {
        return $this->hasMany(PosSale::class);
    }

    public function isRedeemable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $today = now()->startOfDay();

        if ($this->valid_from && $today->lt($this->valid_from->copy()->startOfDay())) {
            return false;
        }

        if ($this->valid_until && $today->gt($this->valid_until->copy()->endOfDay())) {
            return false;
        }

        if ($this->usage_limit > 0 && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }
}
