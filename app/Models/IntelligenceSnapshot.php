<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntelligenceSnapshot extends Model
{
    protected $fillable = [
        'type', 'category', 'branch_id', 'payload', 'meta', 'generated_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'meta' => 'array',
            'generated_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
