<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeliveryTripRequest;
use App\Http\Requests\UpdateDeliveryTripRequest;
use App\Models\DeliveryTrip;
use App\Services\DeliveryTripService;
use Illuminate\Http\RedirectResponse;

class DeliveryTripController extends Controller
{
    public function __construct(
        protected DeliveryTripService $service,
    ) {}

    public function store(StoreDeliveryTripRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('delivery-trips.index')
            ->with('status', __('Delivery trips created successfully.'));
    }

    public function update(UpdateDeliveryTripRequest $request, DeliveryTrip $deliveryTrip): RedirectResponse
    {
        $this->service->update($deliveryTrip, $request->validated());

        return redirect()
            ->route('delivery-trips.index')
            ->with('status', __('Delivery trips updated successfully.'));
    }

    public function destroy(DeliveryTrip $deliveryTrip): RedirectResponse
    {
        $this->service->destroy($deliveryTrip);

        return redirect()
            ->route('delivery-trips.index')
            ->with('status', __('Delivery trips deleted successfully.'));
    }
}
