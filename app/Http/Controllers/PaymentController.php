<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $service,
    ) {}

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('payments.index')
            ->with('status', __('Payment created successfully.'));
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): RedirectResponse
    {
        $this->service->update($payment, $request->validated());

        return redirect()
            ->route('payments.index')
            ->with('status', __('Payment updated successfully.'));
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->service->destroy($payment);

        return redirect()
            ->route('payments.index')
            ->with('status', __('Payment deleted successfully.'));
    }
}
