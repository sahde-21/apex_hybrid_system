<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ApiIndexRequest;
use App\Http\Requests\Api\V1\StoreSaleOrderRequest;
use App\Http\Requests\Api\V1\UpdateSaleOrderRequest;
use App\Http\Requests\Api\V1\WorkflowReasonRequest;
use App\Http\Resources\V1\InvoiceResource;
use App\Http\Resources\V1\SaleOrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\SaleOrder;
use App\Services\Sales\SaleOrderWorkflowService;
use App\Support\Api\ApiAbilities;
use App\Support\Api\ApiIndexQuery;
use Illuminate\Http\JsonResponse;

class SaleOrderController extends ApiController
{
    public function __construct(protected SaleOrderWorkflowService $workflow) {}

    public function index(ApiIndexRequest $request): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::SALES_READ);
        $this->authorize('viewAny', SaleOrder::class);
        $query = (new ApiIndexQuery(SaleOrder::query(), sortable: ['id','reference_number','order_date','status','total_amount','created_at','updated_at'], searchable: ['reference_number'], includes: ['contact','lines']))->apply($request);
        return ApiResponse::paginated(SaleOrderResource::collection($query->paginate($this->perPage($request))), __('scf.api.sale_orders.listed'), $this->meta($request));
    }

    public function show(SaleOrder $saleOrder): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::SALES_READ);
        $this->authorize('view', $saleOrder);
        $saleOrder->load(['contact','lines.product']);
        return $this->respond(new SaleOrderResource($saleOrder), __('scf.api.sale_orders.retrieved'));
    }

    public function store(StoreSaleOrderRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('create', SaleOrder::class);
        $validated = $request->validated();
        $lines = $validated['lines'] ?? [];
        unset($validated['lines']);
        $order = $this->workflow->create($this->actor($request), $validated, $lines);
        $this->logCreated($this->actor($request), $order);
        return $this->respondCreated(new SaleOrderResource($order), __('scf.api.sale_orders.created'), $request);
    }

    public function update(UpdateSaleOrderRequest $request, SaleOrder $saleOrder): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('update', $saleOrder);
        $validated = $request->validated();
        $lines = $validated['lines'] ?? [];
        unset($validated['lines']);
        $order = $this->workflow->update($saleOrder, $this->actor($request), $validated, $lines);
        $this->logUpdated($this->actor($request), $order);
        return $this->respond(new SaleOrderResource($order), __('scf.api.sale_orders.updated'), $request);
    }

    public function destroy(SaleOrder $saleOrder): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('delete', $saleOrder);
        $saleOrder->lines()->delete();
        $saleOrder->delete();
        $this->logDeleted($this->actor(request()), $saleOrder);
        return $this->respondDeleted(__('scf.api.sale_orders.deleted'));
    }

    public function submit(SaleOrder $saleOrder): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('submit', $saleOrder);
        $order = $this->workflow->submit($saleOrder, $this->actor(request()));
        $this->logWorkflow($this->actor(request()), $order, 'submit');
        return $this->respond(new SaleOrderResource($order), __('scf.api.sale_orders.submitted'));
    }

    public function approve(SaleOrder $saleOrder, WorkflowReasonRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('approve', $saleOrder);
        $order = $this->workflow->approve($saleOrder, $this->actor($request), $request->input('reason'));
        $this->logWorkflow($this->actor($request), $order, 'approve');
        return $this->respond(new SaleOrderResource($order), __('scf.api.sale_orders.approved'));
    }

    public function reject(SaleOrder $saleOrder, WorkflowReasonRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('reject', $saleOrder);
        $order = $this->workflow->rejectToDraft($saleOrder, $this->actor($request), $request->input('reason'));
        $this->logWorkflow($this->actor($request), $order, 'reject');
        return $this->respond(new SaleOrderResource($order), __('scf.api.sale_orders.rejected'));
    }

    public function confirm(SaleOrder $saleOrder): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('confirm', $saleOrder);
        $order = $this->workflow->confirm($saleOrder, $this->actor(request()));
        $this->logWorkflow($this->actor(request()), $order, 'confirm');
        return $this->respond(new SaleOrderResource($order), __('scf.api.sale_orders.confirmed'));
    }

    public function cancel(SaleOrder $saleOrder, WorkflowReasonRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('cancel', $saleOrder);
        $order = $this->workflow->cancel($saleOrder, $this->actor($request), $request->input('reason'));
        $this->logWorkflow($this->actor($request), $order, 'cancel');
        return $this->respond(new SaleOrderResource($order), __('scf.api.sale_orders.cancelled'));
    }

    public function createInvoice(SaleOrder $saleOrder): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('invoice', $saleOrder);
        $invoice = $this->workflow->createInvoice($saleOrder, $this->actor(request()));
        $this->logWorkflow($this->actor(request()), $saleOrder, 'create_invoice');
        return $this->respondCreated(new InvoiceResource($invoice->load(['contact','lines'])), __('scf.api.sale_orders.invoiced'));
    }
}
