<?php

namespace App\Models;

use App\Concerns\Auditable;
use Database\Factories\LoyaltyProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $points_per_currency
 * @property bool $is_active
 * @property string|null $description
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'name',
    'code',
    'points_per_currency',
    'is_active',
    'description',
    'created_by',
    'updated_by',
])]
class LoyaltyProgram extends Model
{
    /** @use HasFactory<LoyaltyProgramFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'points_per_currency' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<LoyaltyBalance, $this>
     */
    public function loyaltyBalances(): HasMany
    {
        return $this->hasMany(LoyaltyBalance::class);
    }

    /**
     * @return HasMany<LoyaltyRedemption, $this>
     */
    public function loyaltyRedemptions(): HasMany
    {
        return $this->hasMany(LoyaltyRedemption::class);
    }
}
