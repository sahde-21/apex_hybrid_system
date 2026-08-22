<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ApiIndexRequest;
use App\Http\Requests\Api\V1\StoreBranchRequest;
use App\Http\Requests\Api\V1\UpdateBranchRequest;
use App\Http\Resources\V1\BranchResource;
use App\Http\Responses\ApiResponse;
use App\Models\Branch;
use App\Services\BranchService;
use App\Support\Api\ApiAbilities;
use App\Support\Api\ApiIndexQuery;
use Illuminate\Http\JsonResponse;

class BranchController extends ApiController
{
    public function __construct(
        protected BranchService $service,
    ) {}

    public function index(ApiIndexRequest $request): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::BRANCHES_READ);
        $this->authorize('viewAny', Branch::class);

        $query = (new ApiIndexQuery(
            Branch::query(),
            sortable: ['id', 'name', 'code', 'created_at', 'updated_at', 'is_active'],
            searchable: ['name', 'code', 'address', 'phone', 'email'],
            includes: [],
        ))->apply($request);

        $branches = $query->paginate($this->perPage($request));

        return ApiResponse::paginated(
            BranchResource::collection($branches),
            __('scf.api.branches.listed'),
            $this->meta($request),
        );
    }

    public function show(Branch $branch): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::BRANCHES_READ);
        $this->authorize('view', $branch);

        return $this->respond(new BranchResource($branch), __('scf.api.branches.retrieved'));
    }

    public function store(StoreBranchRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::BRANCHES_WRITE);

        $branch = $this->service->store($request->validated());
        $this->logCreated($this->actor($request), $branch);

        return $this->respondCreated(
            new BranchResource($branch->fresh()),
            __('scf.api.branches.created'),
            $request,
        );
    }

    public function update(UpdateBranchRequest $request, Branch $branch): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::BRANCHES_WRITE);

        $branch = $this->service->update($branch, $request->validated());
        $this->logUpdated($this->actor($request), $branch);

        return $this->respond(
            new BranchResource($branch->fresh()),
            __('scf.api.branches.updated'),
            $request,
        );
    }

    public function destroy(Branch $branch): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::BRANCHES_WRITE);
        $this->authorize('delete', $branch);

        $this->service->destroy($branch);
        $this->logDeleted($this->actor(request()), $branch);

        return $this->respondDeleted(__('scf.api.branches.deleted'));
    }
}
