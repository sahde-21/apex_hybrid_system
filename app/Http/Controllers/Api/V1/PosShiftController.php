<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ApiIndexRequest;
use App\Http\Requests\Api\V1\StorePosShiftRequest;
use App\Http\Requests\Api\V1\UpdatePosShiftRequest;
use App\Http\Resources\V1\PosShiftResource;
use App\Http\Responses\ApiResponse;
use App\Models\PosShift;
use App\Services\PosShiftApiService;
use App\Support\Api\ApiAbilities;
use App\Support\Api\ApiIndexQuery;
use Illuminate\Http\JsonResponse;

class PosShiftController extends ApiController
{
    public function __construct(
        protected PosShiftApiService $service,
    ) {}

    public function index(ApiIndexRequest $request): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::POS_SHIFTS_READ);
        $this->authorize('viewAny', PosShift::class);

        $query = (new ApiIndexQuery(
            PosShift::query(),
            sortable: ['id', 'opened_at', 'closed_at', 'status', 'created_at', 'updated_at'],
            searchable: ['opening_notes', 'closing_notes'],
            includes: ['register', 'user'],
        ))->apply($request);

        $shifts = $query->paginate($this->perPage($request));

        return ApiResponse::paginated(
            PosShiftResource::collection($shifts),
            __('scf.api.pos_shifts.listed'),
            $this->meta($request),
        );
    }

    public function show(PosShift $posShift): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::POS_SHIFTS_READ);
        $this->authorize('view', $posShift);

        return $this->respond(new PosShiftResource($posShift), __('scf.api.pos_shifts.retrieved'));
    }

    public function store(StorePosShiftRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::POS_SHIFTS_WRITE);

        $shift = $this->service->store($request->validated(), $this->actor($request));
        $this->logCreated($this->actor($request), $shift);

        return $this->respondCreated(
            new PosShiftResource($shift->fresh()),
            __('scf.api.pos_shifts.created'),
            $request,
        );
    }

    public function update(UpdatePosShiftRequest $request, PosShift $posShift): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::POS_SHIFTS_WRITE);

        $shift = $this->service->update($posShift, $request->validated(), $this->actor($request));
        $this->logUpdated($this->actor($request), $shift);

        return $this->respond(
            new PosShiftResource($shift->fresh()),
            __('scf.api.pos_shifts.updated'),
            $request,
        );
    }

    public function destroy(PosShift $posShift): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::POS_SHIFTS_WRITE);
        $this->authorize('delete', $posShift);

        $this->service->destroy($posShift);
        $this->logDeleted($this->actor(request()), $posShift);

        return $this->respondDeleted(__('scf.api.pos_shifts.deleted'));
    }
}
