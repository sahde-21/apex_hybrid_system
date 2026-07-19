<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuotationRequest;
use App\Http\Requests\UpdateQuotationRequest;
use App\Models\Quotation;
use App\Services\QuotationService;
use Illuminate\Http\RedirectResponse;

class QuotationController extends Controller
{
    public function __construct(
        protected QuotationService $service,
    ) {}

    public function store(StoreQuotationRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('quotations.index')
            ->with('status', __('Quotation created successfully.'));
    }

    public function update(UpdateQuotationRequest $request, Quotation $quotation): RedirectResponse
    {
        $this->service->update($quotation, $request->validated());

        return redirect()
            ->route('quotations.index')
            ->with('status', __('Quotation updated successfully.'));
    }

    public function destroy(Quotation $quotation): RedirectResponse
    {
        $this->service->destroy($quotation);

        return redirect()
            ->route('quotations.index')
            ->with('status', __('Quotation deleted successfully.'));
    }
}
