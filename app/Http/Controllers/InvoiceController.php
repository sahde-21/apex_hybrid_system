<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $service,
    ) {}

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('invoices.index')
            ->with('status', __('Invoice created successfully.'));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->service->update($invoice, $request->validated());

        return redirect()
            ->route('invoices.index')
            ->with('status', __('Invoice updated successfully.'));
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->service->destroy($invoice);

        return redirect()
            ->route('invoices.index')
            ->with('status', __('Invoice deleted successfully.'));
    }
}
