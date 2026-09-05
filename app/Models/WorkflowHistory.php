<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workflow_instance_id',
    'action',
    'from_status',
    'to_status',
    'comment',
    'approval_level',
    'approval_level_name',
    'user_id',
    'meta',
    'created_at',
])]
class WorkflowHistory extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'approval_level' => 'integer',
            'meta' => 'array',
            'created_at' => 'datetime',
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
