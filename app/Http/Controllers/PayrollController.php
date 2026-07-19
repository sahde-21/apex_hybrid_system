<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePayrollRequest;
use App\Http\Requests\UpdatePayrollRequest;
use App\Models\Payroll;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;

class PayrollController extends Controller
{
    public function __construct(
        protected PayrollService $service,
    ) {}

    public function store(StorePayrollRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('payrolls.index')
            ->with('status', __('Payroll created successfully.'));
    }

    public function update(UpdatePayrollRequest $request, Payroll $payroll): RedirectResponse
    {
        $this->service->update($payroll, $request->validated());

        return redirect()
            ->route('payrolls.index')
            ->with('status', __('Payroll updated successfully.'));
    }

    public function destroy(Payroll $payroll): RedirectResponse
    {
        $this->service->destroy($payroll);

        return redirect()
            ->route('payrolls.index')
            ->with('status', __('Payroll deleted successfully.'));
    }
}
