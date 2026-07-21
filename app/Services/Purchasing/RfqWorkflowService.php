<?php

namespace App\Services\Purchasing;

use App\Enums\PurchaseOrderStatus;
use App\Enums\RfqStatus;
use App\Models\Contact;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Rfq;
use App\Models\RfqLine;
use App\Models\RfqVendor;
use App\Models\User;
use App\Services\Sales\SalesDocumentEventLogger;
use App\Support\Sales\DocumentLineCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RfqWorkflowService
{
    public function __construct(
        protected SalesDocumentEventLogger $events,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     * @param  list<int>  $vendorIds
     */
    public function create(User $user, array $data, array $lines = [], array $vendorIds = []): Rfq
    {
        abort_unless($user->can('rfqs.create'), 403);

        return DB::transaction(function () use ($user, $data, $lines, $vendorIds) {
            $totals = DocumentLineCalculator::summarize($lines);

            $rfq = Rfq::query()->create([
                'reference_number' => $data['reference_number'],
                'purchase_request_id' => $data['purchase_request_id'] ?? null,
                'rfq_date' => $data['rfq_date'],
                'valid_until' => $data['valid_until'] ?? null,
                'status' => RfqStatus::Draft,
                'subtotal_amount' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'] > 0 ? $totals['total'] : ($data['total_amount'] ?? 0),
                'currency_code' => $data['currency_code'] ?? config('accounting.base_currency', 'IQD'),
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
            ]);

            $this->syncLines($rfq, $totals['lines']);
            $this->syncVendors($rfq, $vendorIds ?: ($data['vendor_ids'] ?? []));
            $this->events->log($rfq, 'created', $user, null, RfqStatus::Draft->value);

            return $rfq->fresh(['lines', 'vendors', 'purchaseRequest']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     * @param  list<int>|null  $vendorIds  When null, vendors are not modified
     */
    public function update(Rfq $rfq, User $user, array $data, array $lines = [], ?array $vendorIds = null): Rfq
    {
        abort_unless($user->can('rfqs.update'), 403);

        if (! $rfq->status->isEditable()) {
            throw ValidationException::withMessages([
                'rfq' => [__('scf.purchase_workflow.document_not_editable')],
            ]);
        }

        return DB::transaction(function () use ($rfq, $user, $data, $lines, $vendorIds) {
            $totals = DocumentLineCalculator::summarize($lines);

            $rfq->update([
                'reference_number' => $data['reference_number'] ?? $rfq->reference_number,
                'purchase_request_id' => $data['purchase_request_id'] ?? $rfq->purchase_request_id,
                'rfq_date' => $data['rfq_date'] ?? $rfq->rfq_date,
                'valid_until' => $data['valid_until'] ?? $rfq->valid_until,
                'subtotal_amount' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'] > 0 ? $totals['total'] : ($data['total_amount'] ?? $rfq->total_amount),
                'currency_code' => $data['currency_code'] ?? $rfq->currency_code,
                'notes' => $data['notes'] ?? $rfq->notes,
                'terms' => $data['terms'] ?? $rfq->terms,
            ]);

            $this->syncLines($rfq, $totals['lines']);

            $resolvedVendorIds = $vendorIds ?? $data['vendor_ids'] ?? null;
            if ($resolvedVendorIds !== null) {
                $this->syncVendors($rfq, $resolvedVendorIds);
            }

            $this->events->log($rfq, 'updated', $user);

            return $rfq->fresh(['lines', 'vendors', 'purchaseRequest']);
        });
    }

    public function send(Rfq $rfq, User $user): Rfq
    {
        abort_unless($user->can('rfqs.send') || $user->can('rfqs.update'), 403);

        return DB::transaction(function () use ($rfq, $user) {
            $locked = Rfq::query()->whereKey($rfq->id)->lockForUpdate()->with('vendors')->firstOrFail();

            if ($locked->vendors->isEmpty()) {
                throw ValidationException::withMessages([
                    'vendors' => [__('scf.purchase_workflow.no_vendors')],
                ]);
            }

            return $this->doTransition($locked, $user, RfqStatus::Sent, 'sent');
        });
    }

    public function recordVendorResponse(Rfq $rfq, User $user, int $contactId, array $responseData): Rfq
    {
        abort_unless($user->can('rfqs.update'), 403);

        return DB::transaction(function () use ($rfq, $user, $contactId, $responseData) {
            $locked = Rfq::query()->whereKey($rfq->id)->lockForUpdate()->firstOrFail();

            $vendor = RfqVendor::query()
                ->where('rfq_id', $locked->id)
                ->where('contact_id', $contactId)
                ->lockForUpdate()
                ->firstOrFail();

            $vendor->update([
                'status' => 'responded',
                'quoted_total' => $responseData['quoted_total'] ?? null,
                'quoted_tax' => $responseData['quoted_tax'] ?? null,
                'notes' => $responseData['notes'] ?? $vendor->notes,
                'responded_at' => now(),
            ]);

            if (in_array($locked->status, [RfqStatus::Sent], true)) {
                $this->doTransition($locked, $user, RfqStatus::VendorResponse, 'vendor_responded');
                $locked->refresh();
            } else {
                $this->events->log($locked, 'vendor_responded', $user, $locked->status->value, $locked->status->value, null, (float) ($responseData['quoted_total'] ?? 0));
            }

            return $locked->fresh(['lines', 'vendors']);
        });
    }

    public function accept(Rfq $rfq, User $user, int $selectedVendorId, ?string $reason = null): Rfq
    {
        abort_unless($user->can('rfqs.accept') || $user->can('rfqs.approve'), 403);

        return DB::transaction(function () use ($rfq, $user, $selectedVendorId, $reason) {
            $locked = Rfq::query()->whereKey($rfq->id)->lockForUpdate()->with('vendors')->firstOrFail();

            $vendorExists = $locked->vendors->where('contact_id', $selectedVendorId)->isNotEmpty();
            if (! $vendorExists) {
                throw ValidationException::withMessages([
                    'selected_vendor_id' => [__('scf.purchase_workflow.vendor_required_to_accept')],
                ]);
            }

            $locked->vendors()->update(['is_selected' => false]);
            $locked->vendors()->where('contact_id', $selectedVendorId)->update(['is_selected' => true]);
            $locked->update(['selected_vendor_id' => $selectedVendorId]);

            return $this->doTransition($locked, $user, RfqStatus::Accepted, 'accepted', $reason);
        });
    }

    public function reject(Rfq $rfq, User $user, ?string $reason = null): Rfq
    {
        abort_unless($user->can('rfqs.approve') || $user->can('rfqs.update'), 403);

        return $this->transition($rfq, $user, RfqStatus::Rejected, 'rejected', $reason);
    }

    public function cancel(Rfq $rfq, User $user, ?string $reason = null): Rfq
    {
        abort_unless($user->can('rfqs.update'), 403);

        return $this->transition($rfq, $user, RfqStatus::Cancelled, 'cancelled', $reason);
    }

    public function expire(Rfq $rfq, User $user): Rfq
    {
        return $this->transition($rfq, $user, RfqStatus::Expired, 'expired');
    }

    public function expireIfNeeded(Rfq $rfq, User $user): Rfq
    {
        if (
            $rfq->valid_until
            && $rfq->valid_until->isPast()
            && in_array($rfq->status, [RfqStatus::Draft, RfqStatus::Sent, RfqStatus::VendorResponse], true)
        ) {
            return $this->expire($rfq, $user);
        }

        return $rfq;
    }

    public function duplicate(Rfq $rfq, User $user): Rfq
    {
        abort_unless($user->can('rfqs.create'), 403);
        $rfq->load('lines', 'vendors');

        return $this->create($user, [
            'reference_number' => $rfq->reference_number.'-COPY-'.now()->format('His'),
            'purchase_request_id' => $rfq->purchase_request_id,
            'rfq_date' => now()->toDateString(),
            'valid_until' => $rfq->valid_until?->toDateString(),
            'currency_code' => $rfq->currency_code,
            'notes' => $rfq->notes,
            'terms' => $rfq->terms,
            'total_amount' => $rfq->total_amount,
        ], $rfq->lines->map(fn (RfqLine $line) => [
            'product_id' => $line->product_id,
            'description' => $line->description,
            'quantity' => $line->quantity,
            'unit_price' => $line->unit_price,
            'discount_amount' => $line->discount_amount,
            'tax_amount' => $line->tax_amount,
        ])->all(), $rfq->vendors->pluck('contact_id')->all());
    }

    public function convertToPurchaseOrder(Rfq $rfq, User $user): PurchaseOrder
    {
        abort_unless($user->can('rfqs.convert') || $user->can('rfqs.approve'), 403);

        return DB::transaction(function () use ($rfq, $user) {
            $locked = Rfq::query()->whereKey($rfq->id)->lockForUpdate()->with('lines')->firstOrFail();

            if ($locked->status === RfqStatus::Converted || $locked->converted_purchase_order_id) {
                throw ValidationException::withMessages([
                    'rfq' => [__('scf.purchase_workflow.already_converted')],
                ]);
            }

            if ($locked->status !== RfqStatus::Accepted) {
                throw ValidationException::withMessages([
                    'rfq' => [__('scf.purchase_workflow.must_be_approved_to_convert')],
                ]);
            }

            if (! $locked->selected_vendor_id) {
                throw ValidationException::withMessages([
                    'selected_vendor_id' => [__('scf.purchase_workflow.vendor_required')],
                ]);
            }

            $order = PurchaseOrder::query()->create([
                'reference_number' => 'PO-'.str_replace(['RFQ-', 'R-'], '', $locked->reference_number).'-'.now()->format('ymdHis').'-'.Str::upper(Str::random(4)),
                'contact_id' => $locked->selected_vendor_id,
                'rfq_id' => $locked->id,
                'purchase_request_id' => $locked->purchase_request_id,
                'order_date' => now()->toDateString(),
                'status' => PurchaseOrderStatus::Draft,
                'subtotal_amount' => $locked->subtotal_amount,
                'discount_amount' => $locked->discount_amount,
                'tax_amount' => $locked->tax_amount,
                'total_amount' => $locked->total_amount,
                'currency_code' => $locked->currency_code,
                'notes' => $locked->notes,
                'terms' => $locked->terms,
            ]);

            foreach ($locked->lines as $line) {
                PurchaseOrderLine::query()->create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $line->product_id,
                    'rfq_line_id' => $line->id,
                    'line_number' => $line->line_number,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'discount_amount' => $line->discount_amount,
                    'tax_amount' => $line->tax_amount,
                    'line_total' => $line->line_total,
                    'quantity_billed' => 0,
                ]);
            }

            $from = $locked->status->value;
            $locked->update([
                'status' => RfqStatus::Converted,
                'converted_purchase_order_id' => $order->id,
                'converted_at' => now(),
            ]);

            $this->events->log($locked, 'converted', $user, $from, RfqStatus::Converted->value, null, (float) $locked->total_amount, $order);
            $this->events->log($order, 'created_from_rfq', $user, null, PurchaseOrderStatus::Draft->value, null, (float) $order->total_amount, $locked);

            return $order->fresh(['lines', 'contact', 'rfq']);
        });
    }

    protected function transition(
        Rfq $rfq,
        User $user,
        RfqStatus $to,
        string $event,
        ?string $reason = null,
    ): Rfq {
        return DB::transaction(function () use ($rfq, $user, $to, $event, $reason) {
            $locked = Rfq::query()->whereKey($rfq->id)->lockForUpdate()->firstOrFail();

            return $this->doTransition($locked, $user, $to, $event, $reason);
        });
    }

    protected function doTransition(Rfq $locked, User $user, RfqStatus $to, string $event, ?string $reason = null): Rfq
    {
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

        return $locked->fresh(['lines', 'vendors', 'purchaseRequest', 'convertedPurchaseOrder']);
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    protected function syncLines(Rfq $rfq, array $lines): void
    {
        $rfq->lines()->delete();

        foreach ($lines as $line) {
            if (trim((string) ($line['description'] ?? '')) === '' && empty($line['product_id'])) {
                continue;
            }

            $rfq->lines()->create([
                'product_id' => $line['product_id'] ?? null,
                'purchase_request_line_id' => $line['purchase_request_line_id'] ?? null,
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

    /**
     * @param  list<int>  $vendorIds
     */
    protected function syncVendors(Rfq $rfq, array $vendorIds): void
    {
        $existing = $rfq->vendors()->pluck('contact_id')->all();
        $toAdd = array_diff($vendorIds, $existing);
        $toRemove = array_diff($existing, $vendorIds);

        if ($toRemove !== []) {
            $rfq->vendors()->whereIn('contact_id', $toRemove)->delete();
        }

        foreach ($toAdd as $contactId) {
            if (Contact::query()->whereKey($contactId)->exists()) {
                $rfq->vendors()->create([
                    'contact_id' => $contactId,
                    'status' => 'invited',
                    'is_selected' => false,
                ]);
            }
        }
    }
}
