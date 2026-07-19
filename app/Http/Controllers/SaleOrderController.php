<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleOrderRequest;
use App\Http\Requests\UpdateSaleOrderRequest;
use App\Models\SaleOrder;
use App\Services\SaleOrderService;
use Illuminate\Http\RedirectResponse;

class SaleOrderController extends Controller
{
    public function __construct(
        protected SaleOrderService $service,
    ) {}

    public function store(StoreSaleOrderRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('sale-orders.index')
            ->with('status', __('Sale order created successfully.'));
    }

    public function update(UpdateSaleOrderRequest $request, SaleOrder $saleOrder): RedirectResponse
    {
        $this->service->update($saleOrder, $request->validated());

        return redirect()
            ->route('sale-orders.index')
            ->with('status', __('Sale order updated successfully.'));
    }

    public function destroy(SaleOrder $saleOrder): RedirectResponse
    {
        $this->service->destroy($saleOrder);

        return redirect()
            ->route('sale-orders.index')
            ->with('status', __('Sale order deleted successfully.'));
    }
}
