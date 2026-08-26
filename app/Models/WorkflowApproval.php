<?php

namespace App\Models;

use App\Enums\WorkflowApprovalStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
