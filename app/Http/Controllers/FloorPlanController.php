<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFloorPlanRequest;
use App\Http\Requests\UpdateFloorPlanRequest;
use App\Models\FloorPlan;
use App\Services\FloorPlanService;
use Illuminate\Http\RedirectResponse;

class FloorPlanController extends Controller
{
    public function __construct(
        protected FloorPlanService $service,
    ) {}

    public function store(StoreFloorPlanRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('floor-plans.index')
            ->with('status', __('Floor plans created successfully.'));
    }

    public function update(UpdateFloorPlanRequest $request, FloorPlan $floorPlan): RedirectResponse
    {
        $this->service->update($floorPlan, $request->validated());

        return redirect()
            ->route('floor-plans.index')
            ->with('status', __('Floor plans updated successfully.'));
    }

    public function destroy(FloorPlan $floorPlan): RedirectResponse
    {
        $this->service->destroy($floorPlan);

        return redirect()
            ->route('floor-plans.index')
            ->with('status', __('Floor plans deleted successfully.'));
    }
}
