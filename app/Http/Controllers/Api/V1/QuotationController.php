<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ApiIndexRequest;
use App\Http\Requests\Api\V1\StoreQuotationRequest;
use App\Http\Requests\Api\V1\UpdateQuotationRequest;
use App\Http\Requests\Api\V1\WorkflowReasonRequest;
use App\Http\Resources\V1\QuotationResource;
use App\Http\Resources\V1\SaleOrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\Quotation;
use App\Services\Sales\QuotationWorkflowService;
use App\Support\Api\ApiAbilities;
use App\Support\Api\ApiIndexQuery;
use Illuminate\Http\JsonResponse;

class QuotationController extends ApiController
{
    public function __construct(
        protected QuotationWorkflowService $workflow,
    ) {}

    public function index(ApiIndexRequest $request): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::SALES_READ);
        $this->authorize('viewAny', Quotation::class);

        $query = (new ApiIndexQuery(
            Quotation::query(),
            sortable: ['id', 'reference_number', 'quotation_date', 'status', 'total_amount', 'created_at', 'updated_at'],
            searchable: ['reference_number'],
            includes: ['contact', 'lines'],
        ))->apply($request);

        return ApiResponse::paginated(
            QuotationResource::collection($query->paginate($this->perPage($request))),
            __('scf.api.quotations.listed'),
            $this->meta($request),
        );
    }

    public function show(Quotation $quotation): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::SALES_READ);
        $this->authorize('view', $quotation);
        $quotation->load(['contact', 'lines.product', 'convertedSaleOrder']);

        return $this->respond(new QuotationResource($quotation), __('scf.api.quotations.retrieved'));
    }

    public function store(StoreQuotationRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('create', Quotation::class);

        $validated = $request->validated();
        $lines = $validated['lines'] ?? [];
        unset($validated['lines']);

        $quotation = $this->workflow->create($this->actor($request), $validated, $lines);
        $this->logCreated($this->actor($request), $quotation);

        return $this->respondCreated(new QuotationResource($quotation), __('scf.api.quotations.created'), $request);
    }

    public function update(UpdateQuotationRequest $request, Quotation $quotation): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('update', $quotation);

        $validated = $request->validated();
        $lines = $validated['lines'] ?? [];
        unset($validated['lines']);

        $quotation = $this->workflow->update($quotation, $this->actor($request), $validated, $lines);
        $this->logUpdated($this->actor($request), $quotation);

        return $this->respond(new QuotationResource($quotation), __('scf.api.quotations.updated'), $request);
    }

    public function destroy(Quotation $quotation): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('delete', $quotation);

        $quotation->lines()->delete();
        $quotation->delete();
        $this->logDeleted($this->actor(request()), $quotation);

        return $this->respondDeleted(__('scf.api.quotations.deleted'));
    }

    public function send(Quotation $quotation): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('send', $quotation);

        $quotation = $this->workflow->send($quotation, $this->actor(request()));
        $this->logWorkflow($this->actor(request()), $quotation, 'send');

        return $this->respond(new QuotationResource($quotation), __('scf.api.quotations.sent'));
    }

    public function accept(Quotation $quotation, WorkflowReasonRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('approve', $quotation);

        $quotation = $this->workflow->approve($quotation, $this->actor($request), $request->input('reason'));
        $this->logWorkflow($this->actor($request), $quotation, 'accept');

        return $this->respond(new QuotationResource($quotation), __('scf.api.quotations.accepted'));
    }

    public function reject(Quotation $quotation, WorkflowReasonRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('reject', $quotation);

        $quotation = $this->workflow->reject($quotation, $this->actor($request), $request->input('reason'));
        $this->logWorkflow($this->actor($request), $quotation, 'reject');

        return $this->respond(new QuotationResource($quotation), __('scf.api.quotations.rejected'));
    }

    public function expire(Quotation $quotation): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('update', $quotation);

        $quotation = $this->workflow->expire($quotation, $this->actor(request()));
        $this->logWorkflow($this->actor(request()), $quotation, 'expire');

        return $this->respond(new QuotationResource($quotation), __('scf.api.quotations.expired'));
    }

    public function cancel(Quotation $quotation, WorkflowReasonRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('update', $quotation);

        $quotation = $this->workflow->cancel($quotation, $this->actor($request), $request->input('reason'));
        $this->logWorkflow($this->actor($request), $quotation, 'cancel');

        return $this->respond(new QuotationResource($quotation), __('scf.api.quotations.cancelled'));
    }

    public function duplicate(Quotation $quotation): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('create', Quotation::class);

        $copy = $this->workflow->duplicate($quotation, $this->actor(request()));
        $this->logCreated($this->actor(request()), $copy);

        return $this->respondCreated(new QuotationResource($copy), __('scf.api.quotations.duplicated'));
    }

    public function convertToSaleOrder(Quotation $quotation): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('convert', $quotation);

        $order = $this->workflow->convertToSaleOrder($quotation, $this->actor(request()));
        $this->logWorkflow($this->actor(request()), $quotation, 'convert_to_sale_order');

        return $this->respondCreated(new SaleOrderResource($order->load(['contact', 'lines'])), __('scf.api.quotations.converted'));
    }
}
