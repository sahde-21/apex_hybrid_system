<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductionOrderRequest;
use App\Http\Requests\UpdateProductionOrderRequest;
use App\Models\ProductionOrder;
use App\Services\ProductionOrderService;
use Illuminate\Http\RedirectResponse;

class ProductionOrderController extends Controller
{
    public function __construct(
        protected ProductionOrderService $service,
    ) {}

    public function store(StoreProductionOrderRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('production-orders.index')
            ->with('status', __('Production orders created successfully.'));
    }

    public function update(UpdateProductionOrderRequest $request, ProductionOrder $productionOrder): RedirectResponse
    {
        $this->service->update($productionOrder, $request->validated());

        return redirect()
            ->route('production-orders.index')
            ->with('status', __('Production orders updated successfully.'));
    }

    public function destroy(ProductionOrder $productionOrder): RedirectResponse
    {
        $this->service->destroy($productionOrder);

        return redirect()
            ->route('production-orders.index')
            ->with('status', __('Production orders deleted successfully.'));
    }
}
