<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectTaskRequest;
use App\Http\Requests\UpdateProjectTaskRequest;
use App\Models\ProjectTask;
use App\Services\ProjectTaskService;
use Illuminate\Http\RedirectResponse;

class ProjectTaskController extends Controller
{
    public function __construct(
        protected ProjectTaskService $service,
    ) {}

    public function store(StoreProjectTaskRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('project-tasks.index')
            ->with('status', __('Project tasks created successfully.'));
    }

    public function update(UpdateProjectTaskRequest $request, ProjectTask $projectTask): RedirectResponse
    {
        $this->service->update($projectTask, $request->validated());

        return redirect()
            ->route('project-tasks.index')
            ->with('status', __('Project tasks updated successfully.'));
    }

    public function destroy(ProjectTask $projectTask): RedirectResponse
    {
        $this->service->destroy($projectTask);

        return redirect()
            ->route('project-tasks.index')
            ->with('status', __('Project tasks deleted successfully.'));
    }
}
