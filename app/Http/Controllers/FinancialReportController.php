<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFinancialReportRequest;
use App\Http\Requests\UpdateFinancialReportRequest;
use App\Models\FinancialReport;
use App\Services\FinancialReportService;
use Illuminate\Http\RedirectResponse;

class FinancialReportController extends Controller
{
    public function __construct(
        protected FinancialReportService $service,
    ) {}

    public function store(StoreFinancialReportRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('financial-reports.index')
            ->with('status', __('Financial report created successfully.'));
    }

    public function update(UpdateFinancialReportRequest $request, FinancialReport $financialReport): RedirectResponse
    {
        $this->service->update($financialReport, $request->validated());

        return redirect()
            ->route('financial-reports.index')
            ->with('status', __('Financial report updated successfully.'));
    }

    public function destroy(FinancialReport $financialReport): RedirectResponse
    {
        $this->service->destroy($financialReport);

        return redirect()
            ->route('financial-reports.index')
            ->with('status', __('Financial report deleted successfully.'));
    }
}
