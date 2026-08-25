<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventoryAdjustmentRequest;
use App\Http\Requests\UpdateInventoryAdjustmentRequest;
use App\Models\InventoryAdjustment;
use App\Models\User;
use App\Services\Inventory\InventoryAdjustmentWorkflowService;
use App\Services\InventoryAdjustmentService;
use Illuminate\Http\RedirectResponse;

class InventoryAdjustmentController extends Controller
{
    public function __construct(
        protected InventoryAdjustmentService $service,
        protected InventoryAdjustmentWorkflowService $workflow,
    ) {}

    public function store(StoreInventoryAdjustmentRequest $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $this->workflow->createDraft([
            'reference_number' => (string) $request->validated('reference_number'),
            'product_id' => (int) $request->validated('product_id'),
            'variant_id' => $request->validated('variant_id') !== null ? (int) $request->validated('variant_id') : null,
            'warehouse_id' => (int) $request->validated('warehouse_id'),
            'adjustment_date' => (string) $request->validated('adjustment_date'),
            'quantity_change' => (int) $request->validated('quantity_change'),
            'reason' => (string) $request->validated('reason'),
            'notes' => $request->validated('notes') !== null ? (string) $request->validated('notes') : null,
        ], $user);

        return redirect()
            ->route('inventory-adjustments.index')
            ->with('status', __('Inventory adjustment created successfully.'));
    }

    public function update(UpdateInventoryAdjustmentRequest $request, InventoryAdjustment $inventoryAdjustment): RedirectResponse
    {
        $this->authorize('update', $inventoryAdjustment);

        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $this->workflow->updateDraft($inventoryAdjustment, [
            'reference_number' => (string) $request->validated('reference_number'),
            'product_id' => (int) $request->validated('product_id'),
            'variant_id' => $request->validated('variant_id') !== null ? (int) $request->validated('variant_id') : null,
            'warehouse_id' => (int) $request->validated('warehouse_id'),
            'adjustment_date' => (string) $request->validated('adjustment_date'),
            'quantity_change' => (int) $request->validated('quantity_change'),
            'reason' => (string) $request->validated('reason'),
            'notes' => $request->validated('notes') !== null ? (string) $request->validated('notes') : null,
        ], $user);

        return redirect()
            ->route('inventory-adjustments.index')
            ->with('status', __('Inventory adjustment updated successfully.'));
    }

    public function destroy(InventoryAdjustment $inventoryAdjustment): RedirectResponse
    {
        $this->authorize('delete', $inventoryAdjustment);
        $this->service->destroy($inventoryAdjustment);

        return redirect()
            ->route('inventory-adjustments.index')
            ->with('status', __('Inventory adjustment deleted successfully.'));
    }
}
