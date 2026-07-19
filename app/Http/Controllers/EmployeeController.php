<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Http\RedirectResponse;

class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeService $service,
    ) {}

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('employees.index')
            ->with('status', __('Employee created successfully.'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->service->update($employee, $request->validated());

        return redirect()
            ->route('employees.index')
            ->with('status', __('Employee updated successfully.'));
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->service->destroy($employee);

        return redirect()
            ->route('employees.index')
            ->with('status', __('Employee deleted successfully.'));
    }
}
