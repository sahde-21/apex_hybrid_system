<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $service,
    ) {}

    public function store(StoreSubscriptionRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('subscriptions.index')
            ->with('status', __('Subscriptions created successfully.'));
    }

    public function update(UpdateSubscriptionRequest $request, Subscription $subscription): RedirectResponse
    {
        $this->service->update($subscription, $request->validated());

        return redirect()
            ->route('subscriptions.index')
            ->with('status', __('Subscriptions updated successfully.'));
    }

    public function destroy(Subscription $subscription): RedirectResponse
    {
        $this->service->destroy($subscription);

        return redirect()
            ->route('subscriptions.index')
            ->with('status', __('Subscriptions deleted successfully.'));
    }
}
