<?php

namespace App\Services;

use App\Repositories\ProjectTaskRepository;

class ProjectTaskService extends BaseService
{
    public function __construct(ProjectTaskRepository $repository)
    {
        parent::__construct($repository);
    }
}
