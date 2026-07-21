<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ApiIndexRequest;
use App\Http\Requests\Api\V1\StoreRfqRequest;
use App\Http\Requests\Api\V1\UpdateRfqRequest;
use App\Http\Requests\Api\V1\WorkflowReasonRequest;
use App\Http\Resources\V1\PurchaseOrderResource;
use App\Http\Resources\V1\RfqResource;
use App\Http\Responses\ApiResponse;
use App\Models\Rfq;
use App\Services\Purchasing\RfqWorkflowService;
use App\Support\Api\ApiAbilities;
use App\Support\Api\ApiIndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RfqController extends ApiController
{
    public function __construct(
        protected RfqWorkflowService $workflow,
    ) {}

    public function index(ApiIndexRequest $request): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::PURCHASING_READ);
        $this->authorize('viewAny', Rfq::class);

        $query = (new ApiIndexQuery(
            Rfq::query(),
            sortable: ['id', 'reference_number', 'rfq_date', 'status', 'total_amount', 'created_at', 'updated_at'],
            searchable: ['reference_number'],
            includes: ['lines', 'vendors', 'purchaseRequest'],
        ))->apply($request);

        return ApiResponse::paginated(
            RfqResource::collection($query->paginate($this->perPage($request))),
            __('scf.api.rfqs.listed'),
            $this->meta($request),
        );
    }

    public function show(Rfq $rfq): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::PURCHASING_READ);
        $this->authorize('view', $rfq);
        $rfq->load(['lines.product', 'vendors', 'purchaseRequest', 'convertedPurchaseOrder']);

        return $this->respond(new RfqResource($rfq), __('scf.api.rfqs.retrieved'));
    }

    public function store(StoreRfqRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('create', Rfq::class);

        $validated = $request->validated();
        $lines = $validated['lines'] ?? [];
        $vendorIds = $validated['vendor_ids'] ?? [];
        unset($validated['lines'], $validated['vendor_ids']);

        $rfq = $this->workflow->create($this->actor($request), $validated, $lines, $vendorIds);
        $this->logCreated($this->actor($request), $rfq);

        return $this->respondCreated(new RfqResource($rfq), __('scf.api.rfqs.created'), $request);
    }

    public function update(UpdateRfqRequest $request, Rfq $rfq): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('update', $rfq);

        $validated = $request->validated();
        $lines = $validated['lines'] ?? [];
        $vendorIds = array_key_exists('vendor_ids', $validated) ? ($validated['vendor_ids'] ?? []) : null;
        unset($validated['lines'], $validated['vendor_ids']);

        $rfq = $this->workflow->update($rfq, $this->actor($request), $validated, $lines, $vendorIds);
        $this->logUpdated($this->actor($request), $rfq);

        return $this->respond(new RfqResource($rfq), __('scf.api.rfqs.updated'), $request);
    }

    public function destroy(Rfq $rfq): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('delete', $rfq);

        $rfq->lines()->delete();
        $rfq->vendors()->delete();
        $rfq->delete();
        $this->logDeleted($this->actor(request()), $rfq);

        return $this->respondDeleted(__('scf.api.rfqs.deleted'));
    }

    public function send(Rfq $rfq): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('send', $rfq);

        $rfq = $this->workflow->send($rfq, $this->actor(request()));
        $this->logWorkflow($this->actor(request()), $rfq, 'send');

        return $this->respond(new RfqResource($rfq), __('scf.api.rfqs.sent'));
    }

    public function accept(Rfq $rfq, Request $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('accept', $rfq);

        $validated = $request->validate([
            'selected_vendor_id' => ['required', 'integer', 'exists:contacts,id'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $rfq = $this->workflow->accept(
            $rfq,
            $this->actor($request),
            (int) $validated['selected_vendor_id'],
            $validated['reason'] ?? null,
        );
        $this->logWorkflow($this->actor($request), $rfq, 'accept');

        return $this->respond(new RfqResource($rfq), __('scf.api.rfqs.accepted'));
    }

    public function reject(Rfq $rfq, WorkflowReasonRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('accept', $rfq);

        $rfq = $this->workflow->reject($rfq, $this->actor($request), $request->input('reason'));
        $this->logWorkflow($this->actor($request), $rfq, 'reject');

        return $this->respond(new RfqResource($rfq), __('scf.api.rfqs.rejected'));
    }

    public function expire(Rfq $rfq): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('update', $rfq);

        $rfq = $this->workflow->expire($rfq, $this->actor(request()));
        $this->logWorkflow($this->actor(request()), $rfq, 'expire');

        return $this->respond(new RfqResource($rfq), __('scf.api.rfqs.expired'));
    }

    public function cancel(Rfq $rfq, WorkflowReasonRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('cancel', $rfq);

        $rfq = $this->workflow->cancel($rfq, $this->actor($request), $request->input('reason'));
        $this->logWorkflow($this->actor($request), $rfq, 'cancel');

        return $this->respond(new RfqResource($rfq), __('scf.api.rfqs.cancelled'));
    }

    public function duplicate(Rfq $rfq): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('create', Rfq::class);

        $copy = $this->workflow->duplicate($rfq, $this->actor(request()));
        $this->logCreated($this->actor(request()), $copy);

        return $this->respondCreated(new RfqResource($copy), __('scf.api.rfqs.duplicated'));
    }

    public function convertToPurchaseOrder(Rfq $rfq): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('convert', $rfq);

        $order = $this->workflow->convertToPurchaseOrder($rfq, $this->actor(request()));
        $this->logWorkflow($this->actor(request()), $rfq, 'convert_to_purchase_order');

        return $this->respondCreated(new PurchaseOrderResource($order->load(['contact', 'lines'])), __('scf.api.rfqs.converted'));
    }
}
