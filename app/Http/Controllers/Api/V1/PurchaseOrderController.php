<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ApiIndexRequest;
use App\Http\Requests\Api\V1\StorePurchaseOrderRequest;
use App\Http\Requests\Api\V1\UpdatePurchaseOrderRequest;
use App\Http\Requests\Api\V1\WorkflowReasonRequest;
use App\Http\Resources\V1\BillResource;
use App\Http\Resources\V1\PurchaseOrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\PurchaseOrder;
use App\Services\Purchasing\PurchaseOrderWorkflowService;
use App\Support\Api\ApiAbilities;
use App\Support\Api\ApiIndexQuery;
use Illuminate\Http\JsonResponse;

class PurchaseOrderController extends ApiController
{
    public function __construct(
        protected PurchaseOrderWorkflowService $workflow,
    ) {}

    public function index(ApiIndexRequest $request): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::PURCHASING_READ);
        $this->authorize('viewAny', PurchaseOrder::class);

        $query = (new ApiIndexQuery(
            PurchaseOrder::query(),
            sortable: ['id', 'reference_number', 'order_date', 'status', 'total_amount', 'created_at', 'updated_at'],
            searchable: ['reference_number'],
            includes: ['contact', 'lines', 'rfq'],
        ))->apply($request);

        return ApiResponse::paginated(
            PurchaseOrderResource::collection($query->paginate($this->perPage($request))),
            __('scf.api.purchase_orders.listed'),
            $this->meta($request),
        );
    }

    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::PURCHASING_READ);
        $this->authorize('view', $purchaseOrder);
        $purchaseOrder->load(['contact', 'lines.product', 'rfq']);

        return $this->respond(new PurchaseOrderResource($purchaseOrder), __('scf.api.purchase_orders.retrieved'));
    }

    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('create', PurchaseOrder::class);

        $validated = $request->validated();
        $lines = $validated['lines'] ?? [];
        unset($validated['lines']);

        $order = $this->workflow->create($this->actor($request), $validated, $lines);
        $this->logCreated($this->actor($request), $order);

        return $this->respondCreated(new PurchaseOrderResource($order), __('scf.api.purchase_orders.created'), $request);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('update', $purchaseOrder);

        $validated = $request->validated();
        $lines = $validated['lines'] ?? [];
        unset($validated['lines']);

        $order = $this->workflow->update($purchaseOrder, $this->actor($request), $validated, $lines);
        $this->logUpdated($this->actor($request), $order);

        return $this->respond(new PurchaseOrderResource($order), __('scf.api.purchase_orders.updated'), $request);
    }

    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('delete', $purchaseOrder);

        $purchaseOrder->lines()->delete();
        $purchaseOrder->delete();
        $this->logDeleted($this->actor(request()), $purchaseOrder);

        return $this->respondDeleted(__('scf.api.purchase_orders.deleted'));
    }

    public function submit(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('submit', $purchaseOrder);

        $order = $this->workflow->submit($purchaseOrder, $this->actor(request()));
        $this->logWorkflow($this->actor(request()), $order, 'submit');

        return $this->respond(new PurchaseOrderResource($order), __('scf.api.purchase_orders.submitted'));
    }

    public function approve(PurchaseOrder $purchaseOrder, WorkflowReasonRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('approve', $purchaseOrder);

        $order = $this->workflow->approve($purchaseOrder, $this->actor($request), $request->input('reason'));
        $this->logWorkflow($this->actor($request), $order, 'approve');

        return $this->respond(new PurchaseOrderResource($order), __('scf.api.purchase_orders.approved'));
    }

    public function reject(PurchaseOrder $purchaseOrder, WorkflowReasonRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('approve', $purchaseOrder);

        $order = $this->workflow->rejectToDraft($purchaseOrder, $this->actor($request), $request->input('reason'));
        $this->logWorkflow($this->actor($request), $order, 'reject');

        return $this->respond(new PurchaseOrderResource($order), __('scf.api.purchase_orders.rejected'));
    }

    public function confirm(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('confirm', $purchaseOrder);

        $order = $this->workflow->confirm($purchaseOrder, $this->actor(request()));
        $this->logWorkflow($this->actor(request()), $order, 'confirm');

        return $this->respond(new PurchaseOrderResource($order), __('scf.api.purchase_orders.confirmed'));
    }

    public function cancel(PurchaseOrder $purchaseOrder, WorkflowReasonRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('cancel', $purchaseOrder);

        $order = $this->workflow->cancel($purchaseOrder, $this->actor($request), $request->input('reason'));
        $this->logWorkflow($this->actor($request), $order, 'cancel');

        return $this->respond(new PurchaseOrderResource($order), __('scf.api.purchase_orders.cancelled'));
    }

    public function createBill(PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('bill', $purchaseOrder);

        $bill = $this->workflow->createBill($purchaseOrder, $this->actor(request()));
        $this->logWorkflow($this->actor(request()), $purchaseOrder, 'create_bill');

        return $this->respondCreated(new BillResource($bill->load(['contact', 'lines'])), __('scf.api.purchase_orders.billed'));
    }
}
