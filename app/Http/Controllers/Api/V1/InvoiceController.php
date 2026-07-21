<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ApiIndexRequest;
use App\Http\Requests\Api\V1\StoreInvoiceRequest;
use App\Http\Requests\Api\V1\UpdateInvoiceRequest;
use App\Http\Requests\Api\V1\WorkflowReasonRequest;
use App\Http\Resources\V1\InvoiceResource;
use App\Http\Responses\ApiResponse;
use App\Models\Invoice;
use App\Services\Sales\InvoiceWorkflowService;
use App\Support\Api\ApiAbilities;
use App\Support\Api\ApiIndexQuery;
use Illuminate\Http\JsonResponse;

class InvoiceController extends ApiController
{
    public function __construct(
        protected InvoiceWorkflowService $workflow,
    ) {}

    public function index(ApiIndexRequest $request): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::SALES_READ);
        $this->authorize('viewAny', Invoice::class);

        $query = (new ApiIndexQuery(
            Invoice::query(),
            sortable: ['id', 'reference_number', 'invoice_date', 'status', 'total_amount', 'created_at', 'updated_at'],
            searchable: ['reference_number'],
            includes: ['contact', 'lines'],
        ))->apply($request);

        return ApiResponse::paginated(
            InvoiceResource::collection($query->paginate($this->perPage($request))),
            __('scf.api.invoices.listed'),
            $this->meta($request),
        );
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::SALES_READ);
        $this->authorize('view', $invoice);
        $invoice->load(['contact', 'lines.product', 'saleOrder']);

        return $this->respond(new InvoiceResource($invoice), __('scf.api.invoices.retrieved'));
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('create', Invoice::class);

        $validated = $request->validated();
        $lines = $validated['lines'] ?? [];
        unset($validated['lines']);

        $invoice = $this->workflow->create($this->actor($request), $validated, $lines);
        $this->logCreated($this->actor($request), $invoice);

        return $this->respondCreated(new InvoiceResource($invoice), __('scf.api.invoices.created'), $request);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('update', $invoice);

        $validated = $request->validated();
        $lines = $validated['lines'] ?? [];
        unset($validated['lines']);

        $invoice = $this->workflow->update($invoice, $this->actor($request), $validated, $lines);
        $this->logUpdated($this->actor($request), $invoice);

        return $this->respond(new InvoiceResource($invoice), __('scf.api.invoices.updated'), $request);
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('delete', $invoice);

        $invoice->lines()->delete();
        $invoice->delete();
        $this->logDeleted($this->actor(request()), $invoice);

        return $this->respondDeleted(__('scf.api.invoices.deleted'));
    }

    public function issue(Invoice $invoice): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('issue', $invoice);

        $invoice = $this->workflow->issue($invoice, $this->actor(request()));
        $this->logFinancial($this->actor(request()), $invoice, 'issue');

        return $this->respond(new InvoiceResource($invoice), __('scf.api.invoices.issued'));
    }

    public function void(Invoice $invoice, WorkflowReasonRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('void', $invoice);

        $invoice = $this->workflow->void($invoice, $this->actor($request), $request->input('reason'));
        $this->logFinancial($this->actor($request), $invoice, 'void');

        return $this->respond(new InvoiceResource($invoice), __('scf.api.invoices.voided'));
    }

    public function cancel(Invoice $invoice, WorkflowReasonRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::SALES_WRITE);
        $this->authorize('cancel', $invoice);

        $invoice = $this->workflow->cancel($invoice, $this->actor($request), $request->input('reason'));
        $this->logWorkflow($this->actor($request), $invoice, 'cancel');

        return $this->respond(new InvoiceResource($invoice), __('scf.api.invoices.cancelled'));
    }
}
