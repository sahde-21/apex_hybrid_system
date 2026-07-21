<?php

namespace App\Services\Purchasing;

use App\Enums\PurchaseRequestStatus;
use App\Enums\RfqStatus;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestLine;
use App\Models\Rfq;
use App\Models\RfqLine;
use App\Models\User;
use App\Services\Sales\SalesDocumentEventLogger;
use App\Support\Sales\DocumentLineCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseRequestWorkflowService
{
    public function __construct(
        protected SalesDocumentEventLogger $events,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     */
    public function create(User $user, array $data, array $lines = []): PurchaseRequest
    {
        abort_unless($user->can('purchase-requests.create'), 403);

        return DB::transaction(function () use ($user, $data, $lines) {
            $totals = DocumentLineCalculator::summarize($lines);

            $pr = PurchaseRequest::query()->create([
                'reference_number' => $data['reference_number'],
                'requester_id' => $data['requester_id'] ?? $user->id,
                'department' => $data['department'] ?? null,
                'request_date' => $data['request_date'],
                'needed_by' => $data['needed_by'] ?? null,
                'status' => PurchaseRequestStatus::Draft,
                'subtotal_amount' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'] > 0 ? $totals['total'] : ($data['total_amount'] ?? 0),
                'currency_code' => $data['currency_code'] ?? config('accounting.base_currency', 'IQD'),
                'notes' => $data['notes'] ?? null,
                'attachments' => $data['attachments'] ?? null,
            ]);

            $this->syncLines($pr, $totals['lines']);
            $this->events->log($pr, 'created', $user, null, PurchaseRequestStatus::Draft->value);

            return $pr->fresh(['lines', 'requester']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     */
    public function update(PurchaseRequest $pr, User $user, array $data, array $lines = []): PurchaseRequest
    {
        abort_unless($user->can('purchase-requests.update'), 403);
        $this->assertEditable($pr);

        return DB::transaction(function () use ($pr, $user, $data, $lines) {
            $totals = DocumentLineCalculator::summarize($lines);

            $pr->update([
                'reference_number' => $data['reference_number'] ?? $pr->reference_number,
                'requester_id' => $data['requester_id'] ?? $pr->requester_id,
                'department' => $data['department'] ?? $pr->department,
                'request_date' => $data['request_date'] ?? $pr->request_date,
                'needed_by' => $data['needed_by'] ?? $pr->needed_by,
                'subtotal_amount' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'] > 0 ? $totals['total'] : ($data['total_amount'] ?? $pr->total_amount),
                'currency_code' => $data['currency_code'] ?? $pr->currency_code,
                'notes' => $data['notes'] ?? $pr->notes,
                'attachments' => $data['attachments'] ?? $pr->attachments,
            ]);

            $this->syncLines($pr, $totals['lines']);
            $this->events->log($pr, 'updated', $user);

            return $pr->fresh(['lines', 'requester']);
        });
    }

    public function submit(PurchaseRequest $pr, User $user): PurchaseRequest
    {
        abort_unless($user->can('purchase-requests.submit') || $user->can('purchase-requests.update'), 403);

        return $this->transition($pr, $user, PurchaseRequestStatus::Submitted, 'submitted');
    }

    public function approve(PurchaseRequest $pr, User $user, ?string $reason = null): PurchaseRequest
    {
        abort_unless($user->can('purchase-requests.approve'), 403);

        return $this->transition($pr, $user, PurchaseRequestStatus::Approved, 'approved', $reason);
    }

    public function reject(PurchaseRequest $pr, User $user, ?string $reason = null): PurchaseRequest
    {
        abort_unless($user->can('purchase-requests.approve'), 403);

        return $this->transition($pr, $user, PurchaseRequestStatus::Rejected, 'rejected', $reason);
    }

    public function cancel(PurchaseRequest $pr, User $user, ?string $reason = null): PurchaseRequest
    {
        abort_unless($user->can('purchase-requests.update'), 403);

        return $this->transition($pr, $user, PurchaseRequestStatus::Cancelled, 'cancelled', $reason);
    }

    public function duplicate(PurchaseRequest $pr, User $user): PurchaseRequest
    {
        abort_unless($user->can('purchase-requests.create'), 403);
        $pr->load('lines');

        return $this->create($user, [
            'reference_number' => $pr->reference_number.'-COPY-'.now()->format('His'),
            'requester_id' => $user->id,
            'department' => $pr->department,
            'request_date' => now()->toDateString(),
            'needed_by' => $pr->needed_by?->toDateString(),
            'currency_code' => $pr->currency_code,
            'notes' => $pr->notes,
            'total_amount' => $pr->total_amount,
        ], $pr->lines->map(fn (PurchaseRequestLine $line) => [
            'product_id' => $line->product_id,
            'description' => $line->description,
            'quantity' => $line->quantity,
            'unit_price' => $line->unit_price,
            'discount_amount' => $line->discount_amount,
            'tax_amount' => $line->tax_amount,
        ])->all());
    }

    public function convertToRfq(PurchaseRequest $pr, User $user): Rfq
    {
        abort_unless($user->can('purchase-requests.convert'), 403);

        return DB::transaction(function () use ($pr, $user) {
            $locked = PurchaseRequest::query()->whereKey($pr->id)->lockForUpdate()->with('lines')->firstOrFail();

            if ($locked->status === PurchaseRequestStatus::Converted || $locked->converted_rfq_id) {
                throw ValidationException::withMessages([
                    'purchase_request' => [__('scf.purchase_workflow.already_converted')],
                ]);
            }

            if ($locked->status !== PurchaseRequestStatus::Approved) {
                throw ValidationException::withMessages([
                    'purchase_request' => [__('scf.purchase_workflow.must_be_approved_to_convert')],
                ]);
            }

            $rfq = Rfq::query()->create([
                'reference_number' => 'RFQ-'.str_replace(['PR-', 'REQ-'], '', $locked->reference_number).'-'.now()->format('ymdHis').'-'.Str::upper(Str::random(4)),
                'purchase_request_id' => $locked->id,
                'rfq_date' => now()->toDateString(),
                'status' => RfqStatus::Draft,
                'subtotal_amount' => $locked->subtotal_amount,
                'discount_amount' => $locked->discount_amount,
                'tax_amount' => $locked->tax_amount,
                'total_amount' => $locked->total_amount,
                'currency_code' => $locked->currency_code,
                'notes' => $locked->notes,
            ]);

            foreach ($locked->lines as $line) {
                RfqLine::query()->create([
                    'rfq_id' => $rfq->id,
                    'product_id' => $line->product_id,
                    'purchase_request_line_id' => $line->id,
                    'line_number' => $line->line_number,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'discount_amount' => $line->discount_amount,
                    'tax_amount' => $line->tax_amount,
                    'line_total' => $line->line_total,
                ]);
            }

            $from = $locked->status->value;
            $locked->update([
                'status' => PurchaseRequestStatus::Converted,
                'converted_rfq_id' => $rfq->id,
                'converted_at' => now(),
            ]);

            $this->events->log($locked, 'converted', $user, $from, PurchaseRequestStatus::Converted->value, null, (float) $locked->total_amount, $rfq);
            $this->events->log($rfq, 'created_from_purchase_request', $user, null, RfqStatus::Draft->value, null, (float) $rfq->total_amount, $locked);

            return $rfq->fresh(['lines', 'purchaseRequest']);
        });
    }

    protected function transition(
        PurchaseRequest $pr,
        User $user,
        PurchaseRequestStatus $to,
        string $event,
        ?string $reason = null,
    ): PurchaseRequest {
        return DB::transaction(function () use ($pr, $user, $to, $event, $reason) {
            $locked = PurchaseRequest::query()->whereKey($pr->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo($to)) {
                throw ValidationException::withMessages([
                    'status' => [__('scf.purchase_workflow.invalid_transition', [
                        'from' => $locked->status->label(),
                        'to' => $to->label(),
                    ])],
                ]);
            }

            $from = $locked->status->value;
            $locked->update(['status' => $to]);
            $this->events->log($locked, $event, $user, $from, $to->value, $reason, (float) $locked->total_amount);

            return $locked->fresh(['lines', 'requester']);
        });
    }

    protected function assertEditable(PurchaseRequest $pr): void
    {
        if (! $pr->status->isEditable()) {
            throw ValidationException::withMessages([
                'purchase_request' => [__('scf.purchase_workflow.document_not_editable')],
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    protected function syncLines(PurchaseRequest $pr, array $lines): void
    {
        $pr->lines()->delete();

        foreach ($lines as $line) {
            if (trim((string) ($line['description'] ?? '')) === '' && empty($line['product_id'])) {
                continue;
            }

            $pr->lines()->create([
                'product_id' => $line['product_id'] ?? null,
                'line_number' => $line['line_number'],
                'description' => $line['description'],
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'discount_amount' => $line['discount_amount'],
                'tax_amount' => $line['tax_amount'],
                'line_total' => $line['line_total'],
            ]);
        }
    }
}
