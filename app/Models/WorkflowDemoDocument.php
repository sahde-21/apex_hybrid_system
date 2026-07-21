<?php

namespace App\Models;

use App\Concerns\HasWorkflow;
use App\Contracts\Workflow\Workflowable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Lightweight document used to exercise the workflow engine in tests
 * without coupling to sales or purchasing modules.
 */
#[Fillable([
    'reference_number',
    'definition_key',
    'status',
])]
class WorkflowDemoDocument extends Model implements Workflowable
{
    use HasWorkflow;

    public function workflowDefinitionKey(): string
    {
        return $this->definition_key ?: 'demo-multi-level';
    }
}
