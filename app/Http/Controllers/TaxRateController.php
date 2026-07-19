<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaxRateRequest;
use App\Http\Requests\UpdateTaxRateRequest;
use App\Models\TaxRate;
use App\Services\TaxRateService;
use Illuminate\Http\RedirectResponse;

class TaxRateController extends Controller
{
    public function __construct(
        protected TaxRateService $service,
    ) {}

    public function store(StoreTaxRateRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('tax-rates.index')
            ->with('status', __('Tax rate created successfully.'));
    }

    public function update(UpdateTaxRateRequest $request, TaxRate $taxRate): RedirectResponse
    {
        $this->service->update($taxRate, $request->validated());

        return redirect()
            ->route('tax-rates.index')
            ->with('status', __('Tax rate updated successfully.'));
    }

    public function destroy(TaxRate $taxRate): RedirectResponse
    {
        $this->service->destroy($taxRate);

        return redirect()
            ->route('tax-rates.index')
            ->with('status', __('Tax rate deleted successfully.'));
    }
}
