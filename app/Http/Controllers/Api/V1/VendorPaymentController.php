<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentType;
use App\Http\Requests\Api\V1\ApiIndexRequest;
use App\Http\Requests\Api\V1\StorePaymentRequest;
use App\Http\Requests\Api\V1\UpdatePaymentRequest;
use App\Http\Requests\Api\V1\WorkflowReasonRequest;
use App\Http\Resources\V1\PaymentResource;
use App\Http\Responses\ApiResponse;
use App\Models\Payment;
use App\Services\Sales\PaymentWorkflowService;
use App\Support\Api\ApiAbilities;
use App\Support\Api\ApiIndexQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class VendorPaymentController extends ApiController
{
    public function __construct(
        protected PaymentWorkflowService $workflow,
    ) {}

    public function index(ApiIndexRequest $request): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::PURCHASING_READ);
        $this->authorize('viewAny', Payment::class);

        $baseQuery = Payment::query();

        if (! $request->filled('type')) {
            $baseQuery->where(function (Builder $query): void {
                $query->where('type', PaymentType::Outgoing->value)
                    ->orWhereNotNull('bill_id');
            });
        }

        $query = (new ApiIndexQuery(
            $baseQuery,
            sortable: ['id', 'reference_number', 'payment_date', 'status', 'amount', 'created_at', 'updated_at'],
            searchable: ['reference_number'],
            includes: ['contact', 'invoice', 'bill'],
        ))->apply($request);

        return ApiResponse::paginated(
            PaymentResource::collection($query->paginate($this->perPage($request))),
            __('scf.api.vendor_payments.listed'),
            $this->meta($request),
        );
    }

    public function show(Payment $payment): JsonResponse
    {
        $this->authorizeApiRead(ApiAbilities::PURCHASING_READ);
        $this->authorize('view', $payment);
        $payment->load(['contact', 'invoice', 'bill']);

        return $this->respond(new PaymentResource($payment), __('scf.api.vendor_payments.retrieved'));
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('create', Payment::class);

        $validated = $request->validated();
        $validated['type'] = PaymentType::Outgoing->value;
        $validated['invoice_id'] = null;

        $payment = $this->workflow->create($this->actor($request), $validated);
        $this->logCreated($this->actor($request), $payment);

        return $this->respondCreated(new PaymentResource($payment), __('scf.api.vendor_payments.created'), $request);
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('update', $payment);

        $payment->update($request->validated());
        $payment = $payment->fresh(['contact', 'invoice', 'bill']);
        $this->logUpdated($this->actor($request), $payment);

        return $this->respond(new PaymentResource($payment), __('scf.api.vendor_payments.updated'), $request);
    }

    public function destroy(Payment $payment): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('delete', $payment);

        $payment->delete();
        $this->logDeleted($this->actor(request()), $payment);

        return $this->respondDeleted(__('scf.api.vendor_payments.deleted'));
    }

    public function post(Payment $payment): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('post', $payment);

        $payment = $this->workflow->post($payment, $this->actor(request()));
        $this->logFinancial($this->actor(request()), $payment, 'post');

        return $this->respond(new PaymentResource($payment), __('scf.api.vendor_payments.posted'));
    }

    public function reverse(Payment $payment, WorkflowReasonRequest $request): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('reverse', $payment);

        $reason = $request->input('reason');
        if ($reason === null || trim((string) $reason) === '') {
            throw ValidationException::withMessages([
                'reason' => [__('validation.required', ['attribute' => 'reason'])],
            ]);
        }

        $payment = $this->workflow->reverse($payment, $this->actor($request), $reason);
        $this->logFinancial($this->actor($request), $payment, 'reverse');

        return $this->respond(new PaymentResource($payment), __('scf.api.vendor_payments.reversed'));
    }

    public function cancel(Payment $payment): JsonResponse
    {
        $this->authorizeApiWrite(ApiAbilities::PURCHASING_WRITE);
        $this->authorize('cancel', $payment);

        $payment = $this->workflow->cancel($payment, $this->actor(request()));
        $this->logWorkflow($this->actor(request()), $payment, 'cancel');

        return $this->respond(new PaymentResource($payment), __('scf.api.vendor_payments.cancelled'));
    }
}
