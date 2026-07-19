<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetRequest;
use App\Http\Requests\UpdateBudgetRequest;
use App\Models\Budget;
use App\Services\BudgetService;
use Illuminate\Http\RedirectResponse;

class BudgetController extends Controller
{
    public function __construct(
        protected BudgetService $service,
    ) {}

    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('budgets.index')
            ->with('status', __('Budgeting created successfully.'));
    }

    public function update(UpdateBudgetRequest $request, Budget $budget): RedirectResponse
    {
        $this->service->update($budget, $request->validated());

        return redirect()
            ->route('budgets.index')
            ->with('status', __('Budgeting updated successfully.'));
    }

    public function destroy(Budget $budget): RedirectResponse
    {
        $this->service->destroy($budget);

        return redirect()
            ->route('budgets.index')
            ->with('status', __('Budgeting deleted successfully.'));
    }
}
