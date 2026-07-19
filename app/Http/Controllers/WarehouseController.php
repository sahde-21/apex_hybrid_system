<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\RedirectResponse;

class WarehouseController extends Controller
{
    public function __construct(
        protected WarehouseService $service,
    ) {}

    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('warehouses.index')
            ->with('status', __('Warehouse created successfully.'));
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $this->service->update($warehouse, $request->validated());

        return redirect()
            ->route('warehouses.index')
            ->with('status', __('Warehouse updated successfully.'));
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        $this->service->destroy($warehouse);

        return redirect()
            ->route('warehouses.index')
            ->with('status', __('Warehouse deleted successfully.'));
    }
}
