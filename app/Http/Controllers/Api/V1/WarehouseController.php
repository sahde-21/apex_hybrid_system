<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ApiIndexRequest;
use App\Http\Requests\Api\V1\StoreWarehouseRequest;
use App\Http\Requests\Api\V1\UpdateWarehouseRequest;
use App\Http\Resources\V1\WarehouseResource;
use App\Http\Responses\ApiResponse;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use App\Support\Api\ApiAbilities;
use App\Support\Api\ApiIndexQuery;
use Illuminate\Http\JsonResponse;

class WarehouseController extends ApiController
{
    public function __construct(
        protected WarehouseService $service,
    ) {}

    public function index(ApiIndexRequest $request): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::WAREHOUSES_READ);
        $this->authorize('viewAny', Warehouse::class);

        $query = (new ApiIndexQuery(
            Warehouse::query(),
            sortable: ['id', 'name', 'code', 'created_at', 'updated_at', 'is_active'],
            searchable: ['name', 'code', 'address', 'phone'],
            includes: [],
        ))->apply($request);

        $warehouses = $query->paginate($this->perPage($request));

        return ApiResponse::paginated(
            WarehouseResource::collection($warehouses),
            __('scf.api.warehouses.listed'),
            $this->meta($request),
        );
    }

    public function show(Warehouse $warehouse): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::WAREHOUSES_READ);
        $this->authorize('view', $warehouse);

        return $this->respond(new WarehouseResource($warehouse), __('scf.api.warehouses.retrieved'));
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::WAREHOUSES_WRITE);

        $warehouse = $this->service->store($request->validated());
        $this->logCreated($this->actor($request), $warehouse);

        return $this->respondCreated(
            new WarehouseResource($warehouse->fresh()),
            __('scf.api.warehouses.created'),
            $request,
        );
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::WAREHOUSES_WRITE);

        $warehouse = $this->service->update($warehouse, $request->validated());
        $this->logUpdated($this->actor($request), $warehouse);

        return $this->respond(
            new WarehouseResource($warehouse->fresh()),
            __('scf.api.warehouses.updated'),
            $request,
        );
    }

    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::WAREHOUSES_WRITE);
        $this->authorize('delete', $warehouse);

        $this->service->destroy($warehouse);
        $this->logDeleted($this->actor(request()), $warehouse);

        return $this->respondDeleted(__('scf.api.warehouses.deleted'));
    }
}
