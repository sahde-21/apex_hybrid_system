<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleMaintenanceRequest;
use App\Http\Requests\UpdateVehicleMaintenanceRequest;
use App\Models\VehicleMaintenance;
use App\Services\VehicleMaintenanceService;
use Illuminate\Http\RedirectResponse;

class VehicleMaintenanceController extends Controller
{
    public function __construct(
        protected VehicleMaintenanceService $service,
    ) {}

    public function store(StoreVehicleMaintenanceRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('vehicle-maintenance.index')
            ->with('status', __('Vehicle maintenance created successfully.'));
    }

    public function update(UpdateVehicleMaintenanceRequest $request, VehicleMaintenance $vehicleMaintenance): RedirectResponse
    {
        $this->service->update($vehicleMaintenance, $request->validated());

        return redirect()
            ->route('vehicle-maintenance.index')
            ->with('status', __('Vehicle maintenance updated successfully.'));
    }

    public function destroy(VehicleMaintenance $vehicleMaintenance): RedirectResponse
    {
        $this->service->destroy($vehicleMaintenance);

        return redirect()
            ->route('vehicle-maintenance.index')
            ->with('status', __('Vehicle maintenance deleted successfully.'));
    }
}
