<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBankReconciliationRequest;
use App\Http\Requests\UpdateBankReconciliationRequest;
use App\Models\BankReconciliation;
use App\Services\BankReconciliationService;
use Illuminate\Http\RedirectResponse;

class BankReconciliationController extends Controller
{
    public function __construct(
        protected BankReconciliationService $service,
    ) {}

    public function store(StoreBankReconciliationRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('bank-reconciliations.index')
            ->with('status', __('Bank reconciliation created successfully.'));
    }

    public function update(UpdateBankReconciliationRequest $request, BankReconciliation $bankReconciliation): RedirectResponse
    {
        $this->service->update($bankReconciliation, $request->validated());

        return redirect()
            ->route('bank-reconciliations.index')
            ->with('status', __('Bank reconciliation updated successfully.'));
    }

    public function destroy(BankReconciliation $bankReconciliation): RedirectResponse
    {
        $this->service->destroy($bankReconciliation);

        return redirect()
            ->route('bank-reconciliations.index')
            ->with('status', __('Bank reconciliation deleted successfully.'));
    }
}
