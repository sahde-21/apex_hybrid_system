<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBillRequest;
use App\Http\Requests\UpdateBillRequest;
use App\Models\Bill;
use App\Services\BillService;
use Illuminate\Http\RedirectResponse;

class BillController extends Controller
{
    public function __construct(
        protected BillService $service,
    ) {}

    public function store(StoreBillRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('bills.index')
            ->with('status', __('Bill created successfully.'));
    }

    public function update(UpdateBillRequest $request, Bill $bill): RedirectResponse
    {
        $this->service->update($bill, $request->validated());

        return redirect()
            ->route('bills.index')
            ->with('status', __('Bill updated successfully.'));
    }

    public function destroy(Bill $bill): RedirectResponse
    {
        $this->service->destroy($bill);

        return redirect()
            ->route('bills.index')
            ->with('status', __('Bill deleted successfully.'));
    }
}
