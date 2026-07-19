<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventoryAdjustmentRequest;
use App\Http\Requests\UpdateInventoryAdjustmentRequest;
use App\Models\InventoryAdjustment;
use App\Services\InventoryAdjustmentService;
use Illuminate\Http\RedirectResponse;

class InventoryAdjustmentController extends Controller
{
    public function __construct(
        protected InventoryAdjustmentService $service,
    ) {}

    public function store(StoreInventoryAdjustmentRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('inventory-adjustments.index')
            ->with('status', __('Inventory adjustment created successfully.'));
    }

    public function update(UpdateInventoryAdjustmentRequest $request, InventoryAdjustment $inventoryAdjustment): RedirectResponse
    {
        $this->service->update($inventoryAdjustment, $request->validated());

        return redirect()
            ->route('inventory-adjustments.index')
            ->with('status', __('Inventory adjustment updated successfully.'));
    }

    public function destroy(InventoryAdjustment $inventoryAdjustment): RedirectResponse
    {
        $this->service->destroy($inventoryAdjustment);

        return redirect()
            ->route('inventory-adjustments.index')
            ->with('status', __('Inventory adjustment deleted successfully.'));
    }
}
