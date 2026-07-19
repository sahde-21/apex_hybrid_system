<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;

class PurchaseOrderController extends Controller
{
    public function __construct(
        protected PurchaseOrderService $service,
    ) {}

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('purchase-orders.index')
            ->with('status', __('Purchase order created successfully.'));
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->service->update($purchaseOrder, $request->validated());

        return redirect()
            ->route('purchase-orders.index')
            ->with('status', __('Purchase order updated successfully.'));
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->service->destroy($purchaseOrder);

        return redirect()
            ->route('purchase-orders.index')
            ->with('status', __('Purchase order deleted successfully.'));
    }
}
