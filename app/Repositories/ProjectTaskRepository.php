<?php

namespace App\Repositories;

use App\Models\ProjectTask;

/**
 * @extends BaseRepository<ProjectTask>
 */
class ProjectTaskRepository extends BaseRepository
{
    protected string $model = ProjectTask::class;
}
