<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShippingMethodRequest;
use App\Http\Requests\UpdateShippingMethodRequest;
use App\Models\ShippingMethod;
use App\Services\ShippingMethodService;
use Illuminate\Http\RedirectResponse;

class ShippingMethodController extends Controller
{
    public function __construct(
        protected ShippingMethodService $service,
    ) {}

    public function store(StoreShippingMethodRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('shipping-methods.index')
            ->with('status', __('Shipping methods created successfully.'));
    }

    public function update(UpdateShippingMethodRequest $request, ShippingMethod $shippingMethod): RedirectResponse
    {
        $this->service->update($shippingMethod, $request->validated());

        return redirect()
            ->route('shipping-methods.index')
            ->with('status', __('Shipping methods updated successfully.'));
    }

    public function destroy(ShippingMethod $shippingMethod): RedirectResponse
    {
        $this->service->destroy($shippingMethod);

        return redirect()
            ->route('shipping-methods.index')
            ->with('status', __('Shipping methods deleted successfully.'));
    }
}
