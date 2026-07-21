<?php

namespace App\Models;

use App\Enums\Analytics\InsightSeverity;
use App\Enums\Analytics\InsightStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class IntelligenceRecommendation extends Model
{
    use HasFactory;
    protected $fillable = [
        'rule_key', 'category', 'severity', 'priority', 'status', 'title', 'description',
        'reason', 'suggested_action', 'action_route', 'metrics', 'source_references',
        'subject_type', 'subject_id', 'branch_id', 'generated_at', 'expires_at',
        'acknowledged_at', 'acknowledged_by', 'dismissed_at', 'dismissed_by',
    ];

    protected function casts(): array
    {
        return [
            'severity' => InsightSeverity::class,
            'status' => InsightStatus::class,
            'metrics' => 'array',
            'source_references' => 'array',
            'generated_at' => 'datetime',
            'expires_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'dismissed_at' => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function dismissedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dismissed_by');
    }
}
