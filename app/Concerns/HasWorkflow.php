<?php

namespace App\Concerns;

use App\Contracts\Workflow\Workflowable;
use App\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @mixin Workflowable
 */
trait HasWorkflow
{
    public function workflowInstance(): MorphOne
    {
        return $this->morphOne(WorkflowInstance::class, 'document');
    }

    public function workflowStatus(): string
    {
        $column = $this->workflowStatusColumn();
        $value = $this->getAttribute($column);

        return is_object($value) && property_exists($value, 'value')
            ? (string) $value->value
            : (string) $value;
    }

    public function setWorkflowStatus(string $status): void
    {
        $column = $this->workflowStatusColumn();
        $this->setAttribute($column, $status);
        $this->save();
    }

    protected function workflowStatusColumn(): string
    {
        $definition = config('workflows.definitions.'.$this->workflowDefinitionKey(), []);

        return (string) ($definition['status_column'] ?? 'status');
    }
}
