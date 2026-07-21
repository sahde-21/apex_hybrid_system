<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasWorkflow;
use App\Contracts\Workflow\Workflowable;
use App\Enums\LeaveRequestStatus;
use Database\Factories\LeaveRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $employee_id
 * @property string $leave_type
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property LeaveRequestStatus $status
 * @property string|null $reason
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'employee_id',
    'leave_type',
    'start_date',
    'end_date',
    'status',
    'reason',
    'created_by',
    'updated_by',
])]
class LeaveRequest extends Model implements Workflowable
{
    /** @use HasFactory<LeaveRequestFactory> */
    use Auditable, HasFactory, HasWorkflow, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => LeaveRequestStatus::class,
        ];
    }

    public function workflowDefinitionKey(): string
    {
        return 'leave-requests';
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
