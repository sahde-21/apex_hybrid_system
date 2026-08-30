<?php

namespace App\Models;

use App\Enums\WorkflowApprovalStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workflow_instance_id
 * @property string $action
 * @property int $level
 * @property string $level_name
 * @property WorkflowApprovalStatus $status
 * @property int|null $user_id
 * @property string|null $comment
 * @property Carbon|null $acted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read WorkflowInstance $instance
 * @property-read User|null $user
 */
#[Fillable([
    'workflow_instance_id',
    'action',
    'level',
    'level_name',
    'status',
    'user_id',
    'comment',
    'acted_at',
])]
class WorkflowApproval extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'status' => WorkflowApprovalStatus::class,
            'acted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<WorkflowInstance, $this>
     */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_instance_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
