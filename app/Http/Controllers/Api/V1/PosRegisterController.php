<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ApiIndexRequest;
use App\Http\Requests\Api\V1\StorePosRegisterRequest;
use App\Http\Requests\Api\V1\UpdatePosRegisterRequest;
use App\Http\Resources\V1\PosRegisterResource;
use App\Http\Responses\ApiResponse;
use App\Models\PosRegister;
use App\Services\PosRegisterService;
use App\Support\Api\ApiAbilities;
use App\Support\Api\ApiIndexQuery;
use Illuminate\Http\JsonResponse;

class PosRegisterController extends ApiController
{
    public function __construct(
        protected PosRegisterService $service,
    ) {}

    public function index(ApiIndexRequest $request): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::POS_REGISTERS_READ);
        $this->authorize('viewAny', PosRegister::class);

        $query = (new ApiIndexQuery(
            PosRegister::query(),
            sortable: ['id', 'name', 'code', 'created_at', 'updated_at', 'is_active'],
            searchable: ['name', 'code', 'notes'],
            includes: ['warehouse', 'branch'],
        ))->apply($request);

        $registers = $query->paginate($this->perPage($request));

        return ApiResponse::paginated(
            PosRegisterResource::collection($registers),
            __('scf.api.pos_registers.listed'),
            $this->meta($request),
        );
    }

    public function show(PosRegister $posRegister): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::POS_REGISTERS_READ);
        $this->authorize('view', $posRegister);

        return $this->respond(new PosRegisterResource($posRegister), __('scf.api.pos_registers.retrieved'));
    }

    public function store(StorePosRegisterRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::POS_REGISTERS_WRITE);

        $register = $this->service->store($request->validated());
        $this->logCreated($this->actor($request), $register);

        return $this->respondCreated(
            new PosRegisterResource($register->fresh()),
            __('scf.api.pos_registers.created'),
            $request,
        );
    }

    public function update(UpdatePosRegisterRequest $request, PosRegister $posRegister): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::POS_REGISTERS_WRITE);

        $register = $this->service->update($posRegister, $request->validated());
        $this->logUpdated($this->actor($request), $register);

        return $this->respond(
            new PosRegisterResource($register->fresh()),
            __('scf.api.pos_registers.updated'),
            $request,
        );
    }

    public function destroy(PosRegister $posRegister): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::POS_REGISTERS_WRITE);
        $this->authorize('delete', $posRegister);

        $this->service->destroy($posRegister);
        $this->logDeleted($this->actor(request()), $posRegister);

        return $this->respondDeleted(__('scf.api.pos_registers.deleted'));
    }
}
