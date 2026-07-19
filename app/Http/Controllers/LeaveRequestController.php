<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveRequestRequest;
use App\Http\Requests\UpdateLeaveRequestRequest;
use App\Models\LeaveRequest;
use App\Services\LeaveRequestService;
use Illuminate\Http\RedirectResponse;

class LeaveRequestController extends Controller
{
    public function __construct(
        protected LeaveRequestService $service,
    ) {}

    public function store(StoreLeaveRequestRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('leave-requests.index')
            ->with('status', __('Leave requests created successfully.'));
    }

    public function update(UpdateLeaveRequestRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->service->update($leaveRequest, $request->validated());

        return redirect()
            ->route('leave-requests.index')
            ->with('status', __('Leave requests updated successfully.'));
    }

    public function destroy(LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->service->destroy($leaveRequest);

        return redirect()
            ->route('leave-requests.index')
            ->with('status', __('Leave requests deleted successfully.'));
    }
}
