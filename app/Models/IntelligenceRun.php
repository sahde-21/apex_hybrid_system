<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntelligenceRun extends Model
{
    protected $fillable = [
        'type', 'status', 'records_generated', 'duration_ms', 'meta', 'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
