<?php

namespace App\Models;

use App\Enums\WorkflowApprovalStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'definition_key',
    'document_type',
    'document_id',
    'current_status',
    'current_approval_level',
    'approval_mode',
    'meta',
])]
class WorkflowInstance extends Model
{
    protected function casts(): array
    {
        return [
            'current_approval_level' => 'integer',
            'meta' => 'array',
        ];
    }

    public function document(): MorphTo
    {
        return $this->morphTo();
    }

    public function histories(): HasMany
    {
        return $this->hasMany(WorkflowHistory::class)->latest('created_at');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(WorkflowApproval::class)->orderBy('level');
    }

    public function pendingApprovals(): HasMany
    {
        return $this->approvals()->where('status', WorkflowApprovalStatus::Pending->value);
    }
}
