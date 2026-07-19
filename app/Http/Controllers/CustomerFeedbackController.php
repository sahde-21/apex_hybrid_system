<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerFeedbackRequest;
use App\Http\Requests\UpdateCustomerFeedbackRequest;
use App\Models\CustomerFeedback;
use App\Services\CustomerFeedbackService;
use Illuminate\Http\RedirectResponse;

class CustomerFeedbackController extends Controller
{
    public function __construct(
        protected CustomerFeedbackService $service,
    ) {}

    public function store(StoreCustomerFeedbackRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('customer-feedback.index')
            ->with('status', __('Customer feedback created successfully.'));
    }

    public function update(UpdateCustomerFeedbackRequest $request, CustomerFeedback $customerFeedback): RedirectResponse
    {
        $this->service->update($customerFeedback, $request->validated());

        return redirect()
            ->route('customer-feedback.index')
            ->with('status', __('Customer feedback updated successfully.'));
    }

    public function destroy(CustomerFeedback $customerFeedback): RedirectResponse
    {
        $this->service->destroy($customerFeedback);

        return redirect()
            ->route('customer-feedback.index')
            ->with('status', __('Customer feedback deleted successfully.'));
    }
}
