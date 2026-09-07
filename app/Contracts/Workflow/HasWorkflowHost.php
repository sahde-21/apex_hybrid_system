<?php

namespace App\Contracts\Workflow;

use App\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model that hosts a workflow instance.
 *
 * @property-read WorkflowInstance|null $workflowInstance
 *
 * @mixin Model
 */
interface HasWorkflowHost extends Workflowable {}
