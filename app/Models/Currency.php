<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'symbol',
    'decimal_places',
    'is_base',
    'is_active',
])]
class Currency extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'is_base' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ExchangeRate, $this>
     */
    public function rates(): HasMany
    {
        return $this->hasMany(ExchangeRate::class);
    }
}
