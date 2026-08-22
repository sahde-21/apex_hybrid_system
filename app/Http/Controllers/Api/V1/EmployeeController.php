<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ApiIndexRequest;
use App\Http\Requests\Api\V1\StoreEmployeeRequest;
use App\Http\Requests\Api\V1\UpdateEmployeeRequest;
use App\Http\Resources\V1\EmployeeResource;
use App\Http\Responses\ApiResponse;
use App\Models\Employee;
use App\Services\EmployeeService;
use App\Support\Api\ApiAbilities;
use App\Support\Api\ApiIndexQuery;
use Illuminate\Http\JsonResponse;

class EmployeeController extends ApiController
{
    public function __construct(
        protected EmployeeService $service,
    ) {}

    public function index(ApiIndexRequest $request): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::EMPLOYEES_READ);
        $this->authorize('viewAny', Employee::class);

        $query = (new ApiIndexQuery(
            Employee::query(),
            sortable: ['id', 'employee_number', 'first_name', 'last_name', 'hire_date', 'created_at', 'updated_at', 'is_active'],
            searchable: ['employee_number', 'first_name', 'last_name', 'email', 'phone', 'department', 'job_title'],
            includes: [],
        ))->apply($request);

        $employees = $query->paginate($this->perPage($request));

        return ApiResponse::paginated(
            EmployeeResource::collection($employees),
            __('scf.api.employees.listed'),
            $this->meta($request),
        );
    }

    public function show(Employee $employee): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::EMPLOYEES_READ);
        $this->authorize('view', $employee);

        return $this->respond(new EmployeeResource($employee), __('scf.api.employees.retrieved'));
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::EMPLOYEES_WRITE);

        $employee = $this->service->store($request->validated());
        $this->logCreated($this->actor($request), $employee);

        return $this->respondCreated(
            new EmployeeResource($employee->fresh()),
            __('scf.api.employees.created'),
            $request,
        );
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::EMPLOYEES_WRITE);

        $employee = $this->service->update($employee, $request->validated());
        $this->logUpdated($this->actor($request), $employee);

        return $this->respond(
            new EmployeeResource($employee->fresh()),
            __('scf.api.employees.updated'),
            $request,
        );
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::EMPLOYEES_WRITE);
        $this->authorize('delete', $employee);

        $this->service->destroy($employee);
        $this->logDeleted($this->actor(request()), $employee);

        return $this->respondDeleted(__('scf.api.employees.deleted'));
    }
}
