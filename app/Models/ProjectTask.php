<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\ProjectTaskStatus;
use Database\Factories\ProjectTaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $contract_id
 * @property int|null $employee_id
 * @property string $title
 * @property Carbon $due_date
 * @property string|null $priority
 * @property ProjectTaskStatus $status
 * @property string|null $description
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'contract_id',
    'employee_id',
    'title',
    'due_date',
    'priority',
    'status',
    'description',
    'created_by',
    'updated_by',
])]
class ProjectTask extends Model
{
    /** @use HasFactory<ProjectTaskFactory> */
    use Auditable, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'status' => ProjectTaskStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
