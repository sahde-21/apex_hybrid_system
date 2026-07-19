<?php

namespace App\Models;

use App\Concerns\Auditable;
use Database\Factories\PosRegisterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property int|null $warehouse_id
 * @property int|null $branch_id
 * @property bool $is_active
 * @property bool $cash_drawer_enabled
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'name',
    'code',
    'warehouse_id',
    'branch_id',
    'is_active',
    'cash_drawer_enabled',
    'notes',
    'created_by',
    'updated_by',
])]
class PosRegister extends Model
{
    /** @use HasFactory<PosRegisterFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'cash_drawer_enabled' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * @return HasMany<PosShift, $this>
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(PosShift::class);
    }

    public function openShift(): ?PosShift
    {
        return $this->shifts()->where('status', 'open')->latest('opened_at')->first();
    }
}
