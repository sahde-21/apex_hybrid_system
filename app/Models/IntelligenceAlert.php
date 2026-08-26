<?php

namespace App\Models;

use App\Enums\Analytics\InsightSeverity;
use App\Enums\Analytics\InsightStatus;
use Database\Factories\IntelligenceAlertFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class IntelligenceAlert extends Model
{
    /** @use HasFactory<IntelligenceAlertFactory> */
    use HasFactory;

    protected $fillable = [
        'rule_key', 'category', 'severity', 'status', 'title', 'summary', 'explanation',
        'metrics', 'source_references', 'subject_type', 'subject_id', 'branch_id',
        'detected_at', 'expires_at', 'acknowledged_at', 'acknowledged_by',
        'dismissed_at', 'dismissed_by',
    ];

    protected function casts(): array
    {
        return [
            'severity' => InsightSeverity::class,
            'status' => InsightStatus::class,
            'metrics' => 'array',
            'source_references' => 'array',
            'detected_at' => 'datetime',
            'expires_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'dismissed_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function dismissedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dismissed_by');
    }

    public function isActive(): bool
    {
        return $this->status === InsightStatus::Active;
    }
}
