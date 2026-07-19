<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\RedirectResponse;

class ExpenseController extends Controller
{
    public function __construct(
        protected ExpenseService $service,
    ) {}

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('expenses.index')
            ->with('status', __('Expense created successfully.'));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->service->update($expense, $request->validated());

        return redirect()
            ->route('expenses.index')
            ->with('status', __('Expense updated successfully.'));
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->service->destroy($expense);

        return redirect()
            ->route('expenses.index')
            ->with('status', __('Expense deleted successfully.'));
    }
}
