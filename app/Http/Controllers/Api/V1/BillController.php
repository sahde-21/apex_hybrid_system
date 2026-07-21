<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ApiIndexRequest;
use App\Http\Requests\Api\V1\StoreBillRequest;
use App\Http\Requests\Api\V1\UpdateBillRequest;
use App\Http\Requests\Api\V1\WorkflowReasonRequest;
use App\Http\Resources\V1\BillResource;
use App\Http\Responses\ApiResponse;
use App\Models\Bill;
use App\Services\Purchasing\BillWorkflowService;
use App\Support\Api\ApiAbilities;
use App\Support\Api\ApiIndexQuery;
use Illuminate\Http\JsonResponse;

class BillController extends ApiController
{
    public function __construct(
        protected BillWorkflowService $workflow,
    ) {}

    public function index(ApiIndexRequest $request): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::PURCHASING_READ);
        $this->authorize('viewAny', Bill::class);

        $query = (new ApiIndexQuery(
            Bill::query(),
            sortable: ['id', 'reference_number', 'bill_date', 'status', 'total_amount', 'created_at', 'updated_at'],
            searchable: ['reference_number'],
            includes: ['contact', 'lines', 'purchaseOrder'],
        ))->apply($request);

        return ApiResponse::paginated(
            BillResource::collection($query->paginate($this->perPage($request))),
            __('scf.api.bills.listed'),
            $this->meta($request),
        );
    }

    public function show(Bill $bill): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::PURCHASING_READ);
        $this->authorize('view', $bill);
        $bill->load(['contact', 'lines.product', 'purchaseOrder']);

        return $this->respond(new BillResource($bill), __('scf.api.bills.retrieved'));
    }

    public function store(StoreBillRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('create', Bill::class);

        $validated = $request->validated();
        $lines = $validated['lines'] ?? [];
        unset($validated['lines']);

        $bill = $this->workflow->create($this->actor($request), $validated, $lines);
        $this->logCreated($this->actor($request), $bill);

        return $this->respondCreated(new BillResource($bill), __('scf.api.bills.created'), $request);
    }

    public function update(UpdateBillRequest $request, Bill $bill): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('update', $bill);

        $validated = $request->validated();
        $lines = $validated['lines'] ?? [];
        unset($validated['lines']);

        $bill = $this->workflow->update($bill, $this->actor($request), $validated, $lines);
        $this->logUpdated($this->actor($request), $bill);

        return $this->respond(new BillResource($bill), __('scf.api.bills.updated'), $request);
    }

    public function destroy(Bill $bill): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('delete', $bill);

        $bill->lines()->delete();
        $bill->delete();
        $this->logDeleted($this->actor(request()), $bill);

        return $this->respondDeleted(__('scf.api.bills.deleted'));
    }

    public function issue(Bill $bill): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('issue', $bill);

        $bill = $this->workflow->issue($bill, $this->actor(request()));
        $this->logFinancial($this->actor(request()), $bill, 'issue');

        return $this->respond(new BillResource($bill), __('scf.api.bills.issued'));
    }

    public function void(Bill $bill, WorkflowReasonRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('void', $bill);

        $bill = $this->workflow->void($bill, $this->actor($request), $request->input('reason'));
        $this->logFinancial($this->actor($request), $bill, 'void');

        return $this->respond(new BillResource($bill), __('scf.api.bills.voided'));
    }

    public function cancel(Bill $bill, WorkflowReasonRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('cancel', $bill);

        $bill = $this->workflow->cancel($bill, $this->actor($request), $request->input('reason'));
        $this->logWorkflow($this->actor($request), $bill, 'cancel');

        return $this->respond(new BillResource($bill), __('scf.api.bills.cancelled'));
    }
}
