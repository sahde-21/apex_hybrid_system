<?php

namespace App\Models;

use App\Concerns\Auditable;
use Database\Factories\ShippingMethodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $carrier
 * @property string $base_cost
 * @property bool $is_active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'name',
    'code',
    'carrier',
    'base_cost',
    'is_active',
    'created_by',
    'updated_by',
])]
class ShippingMethod extends Model
{
    /** @use HasFactory<ShippingMethodFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
