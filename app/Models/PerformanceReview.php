<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\PerformanceReviewStatus;
use Database\Factories\PerformanceReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $employee_id
 * @property Carbon $review_date
 * @property int $rating
 * @property PerformanceReviewStatus $status
 * @property string|null $comments
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'employee_id',
    'review_date',
    'rating',
    'status',
    'comments',
    'created_by',
    'updated_by',
])]
class PerformanceReview extends Model
{
    /** @use HasFactory<PerformanceReviewFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'review_date' => 'date',
            'rating' => 'integer',
            'status' => PerformanceReviewStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
