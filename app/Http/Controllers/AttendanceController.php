<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $service,
    ) {}

    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('attendance.index')
            ->with('status', __('Attendance created successfully.'));
    }

    public function update(UpdateAttendanceRequest $request, Attendance $attendance): RedirectResponse
    {
        $this->service->update($attendance, $request->validated());

        return redirect()
            ->route('attendance.index')
            ->with('status', __('Attendance updated successfully.'));
    }

    public function destroy(Attendance $attendance): RedirectResponse
    {
        $this->service->destroy($attendance);

        return redirect()
            ->route('attendance.index')
            ->with('status', __('Attendance deleted successfully.'));
    }
}
