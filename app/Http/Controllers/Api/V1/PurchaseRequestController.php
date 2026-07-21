<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ApiIndexRequest;
use App\Http\Requests\Api\V1\StorePurchaseRequestRequest;
use App\Http\Requests\Api\V1\UpdatePurchaseRequestRequest;
use App\Http\Requests\Api\V1\WorkflowReasonRequest;
use App\Http\Resources\V1\PurchaseRequestResource;
use App\Http\Resources\V1\RfqResource;
use App\Http\Responses\ApiResponse;
use App\Models\PurchaseRequest;
use App\Services\Purchasing\PurchaseRequestWorkflowService;
use App\Support\Api\ApiAbilities;
use App\Support\Api\ApiIndexQuery;
use Illuminate\Http\JsonResponse;

class PurchaseRequestController extends ApiController
{
    public function __construct(
        protected PurchaseRequestWorkflowService $workflow,
    ) {}

    public function index(ApiIndexRequest $request): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::PURCHASING_READ);
        $this->authorize('viewAny', PurchaseRequest::class);

        $query = (new ApiIndexQuery(
            PurchaseRequest::query(),
            sortable: ['id', 'reference_number', 'request_date', 'status', 'total_amount', 'created_at', 'updated_at'],
            searchable: ['reference_number'],
            includes: ['requester', 'lines'],
        ))->apply($request);

        return ApiResponse::paginated(
            PurchaseRequestResource::collection($query->paginate($this->perPage($request))),
            __('scf.api.purchase_requests.listed'),
            $this->meta($request),
        );
    }

    public function show(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::PURCHASING_READ);
        $this->authorize('view', $purchaseRequest);
        $purchaseRequest->load(['requester', 'lines.product']);

        return $this->respond(new PurchaseRequestResource($purchaseRequest), __('scf.api.purchase_requests.retrieved'));
    }

    public function store(StorePurchaseRequestRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('create', PurchaseRequest::class);

        $validated = $request->validated();
        $lines = $validated['lines'] ?? [];
        unset($validated['lines']);

        $purchaseRequest = $this->workflow->create($this->actor($request), $validated, $lines);
        $this->logCreated($this->actor($request), $purchaseRequest);

        return $this->respondCreated(new PurchaseRequestResource($purchaseRequest), __('scf.api.purchase_requests.created'), $request);
    }

    public function update(UpdatePurchaseRequestRequest $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('update', $purchaseRequest);

        $validated = $request->validated();
        $lines = $validated['lines'] ?? [];
        unset($validated['lines']);

        $purchaseRequest = $this->workflow->update($purchaseRequest, $this->actor($request), $validated, $lines);
        $this->logUpdated($this->actor($request), $purchaseRequest);

        return $this->respond(new PurchaseRequestResource($purchaseRequest), __('scf.api.purchase_requests.updated'), $request);
    }

    public function destroy(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('delete', $purchaseRequest);

        $purchaseRequest->lines()->delete();
        $purchaseRequest->delete();
        $this->logDeleted($this->actor(request()), $purchaseRequest);

        return $this->respondDeleted(__('scf.api.purchase_requests.deleted'));
    }

    public function submit(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('submit', $purchaseRequest);

        $purchaseRequest = $this->workflow->submit($purchaseRequest, $this->actor(request()));
        $this->logWorkflow($this->actor(request()), $purchaseRequest, 'submit');

        return $this->respond(new PurchaseRequestResource($purchaseRequest), __('scf.api.purchase_requests.submitted'));
    }

    public function approve(PurchaseRequest $purchaseRequest, WorkflowReasonRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('approve', $purchaseRequest);

        $purchaseRequest = $this->workflow->approve($purchaseRequest, $this->actor($request), $request->input('reason'));
        $this->logWorkflow($this->actor($request), $purchaseRequest, 'approve');

        return $this->respond(new PurchaseRequestResource($purchaseRequest), __('scf.api.purchase_requests.approved'));
    }

    public function reject(PurchaseRequest $purchaseRequest, WorkflowReasonRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('approve', $purchaseRequest);

        $purchaseRequest = $this->workflow->reject($purchaseRequest, $this->actor($request), $request->input('reason'));
        $this->logWorkflow($this->actor($request), $purchaseRequest, 'reject');

        return $this->respond(new PurchaseRequestResource($purchaseRequest), __('scf.api.purchase_requests.rejected'));
    }

    public function cancel(PurchaseRequest $purchaseRequest, WorkflowReasonRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('cancel', $purchaseRequest);

        $purchaseRequest = $this->workflow->cancel($purchaseRequest, $this->actor($request), $request->input('reason'));
        $this->logWorkflow($this->actor($request), $purchaseRequest, 'cancel');

        return $this->respond(new PurchaseRequestResource($purchaseRequest), __('scf.api.purchase_requests.cancelled'));
    }

    public function convertToRfq(PurchaseRequest $purchaseRequest): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('convert', $purchaseRequest);

        $rfq = $this->workflow->convertToRfq($purchaseRequest, $this->actor(request()));
        $this->logWorkflow($this->actor(request()), $purchaseRequest, 'convert_to_rfq');

        return $this->respondCreated(new RfqResource($rfq->load(['lines', 'purchaseRequest'])), __('scf.api.purchase_requests.converted'));
    }
}
