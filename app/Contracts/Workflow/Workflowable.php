<?php

namespace App\Contracts\Workflow;

use App\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @mixin Model
 */
interface Workflowable
{
    /**
     * Key matching config('workflows.definitions.{key}').
     */
    public function workflowDefinitionKey(): string;

    public function workflowStatus(): string;

    public function setWorkflowStatus(string $status): void;

    /**
     * @return MorphOne<WorkflowInstance, Model>
     */
    public function workflowInstance(): MorphOne;
}
