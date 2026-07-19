<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTimeLogRequest;
use App\Http\Requests\UpdateTimeLogRequest;
use App\Models\TimeLog;
use App\Services\TimeLogService;
use Illuminate\Http\RedirectResponse;

class TimeLogController extends Controller
{
    public function __construct(
        protected TimeLogService $service,
    ) {}

    public function store(StoreTimeLogRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('time-logs.index')
            ->with('status', __('Time logs created successfully.'));
    }

    public function update(UpdateTimeLogRequest $request, TimeLog $timeLog): RedirectResponse
    {
        $this->service->update($timeLog, $request->validated());

        return redirect()
            ->route('time-logs.index')
            ->with('status', __('Time logs updated successfully.'));
    }

    public function destroy(TimeLog $timeLog): RedirectResponse
    {
        $this->service->destroy($timeLog);

        return redirect()
            ->route('time-logs.index')
            ->with('status', __('Time logs deleted successfully.'));
    }
}
