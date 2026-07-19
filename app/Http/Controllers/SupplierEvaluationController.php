<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierEvaluationRequest;
use App\Http\Requests\UpdateSupplierEvaluationRequest;
use App\Models\SupplierEvaluation;
use App\Services\SupplierEvaluationService;
use Illuminate\Http\RedirectResponse;

class SupplierEvaluationController extends Controller
{
    public function __construct(
        protected SupplierEvaluationService $service,
    ) {}

    public function store(StoreSupplierEvaluationRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('supplier-evaluations.index')
            ->with('status', __('Supplier evaluations created successfully.'));
    }

    public function update(UpdateSupplierEvaluationRequest $request, SupplierEvaluation $supplierEvaluation): RedirectResponse
    {
        $this->service->update($supplierEvaluation, $request->validated());

        return redirect()
            ->route('supplier-evaluations.index')
            ->with('status', __('Supplier evaluations updated successfully.'));
    }

    public function destroy(SupplierEvaluation $supplierEvaluation): RedirectResponse
    {
        $this->service->destroy($supplierEvaluation);

        return redirect()
            ->route('supplier-evaluations.index')
            ->with('status', __('Supplier evaluations deleted successfully.'));
    }
}
