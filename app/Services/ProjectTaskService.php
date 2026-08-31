<?php

namespace App\Services;

use App\Repositories\ProjectTaskRepository;
use App\Models\ProjectTask;

/**
 * @extends BaseService<ProjectTask>
 */
class ProjectTaskService extends BaseService
{
    public function __construct(ProjectTaskRepository $repository)
    {
        parent::__construct($repository);
    }
}
