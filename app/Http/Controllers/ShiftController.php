<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShiftRequest;
use App\Http\Requests\UpdateShiftRequest;
use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Http\RedirectResponse;

class ShiftController extends Controller
{
    public function __construct(
        protected ShiftService $service,
    ) {}

    public function store(StoreShiftRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('shifts.index')
            ->with('status', __('Shift management created successfully.'));
    }

    public function update(UpdateShiftRequest $request, Shift $shift): RedirectResponse
    {
        $this->service->update($shift, $request->validated());

        return redirect()
            ->route('shifts.index')
            ->with('status', __('Shift management updated successfully.'));
    }

    public function destroy(Shift $shift): RedirectResponse
    {
        $this->service->destroy($shift);

        return redirect()
            ->route('shifts.index')
            ->with('status', __('Shift management deleted successfully.'));
    }
}
