<?php

namespace App\Services\Sales;

use App\Enums\QuotationStatus;
use App\Enums\SaleOrderStatus;
use App\Models\Quotation;
use App\Models\QuotationLine;
use App\Models\SaleOrder;
use App\Models\SaleOrderLine;
use App\Models\User;
use App\Support\Sales\DocumentLineCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuotationWorkflowService
{
    public function __construct(
        protected SalesDocumentEventLogger $events,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     */
    public function create(User $user, array $data, array $lines = []): Quotation
    {
        abort_unless($user->can('quotations.create'), 403);

        return DB::transaction(function () use ($user, $data, $lines) {
            $totals = DocumentLineCalculator::summarize($lines);

            $quotation = Quotation::query()->create([
                'reference_number' => $data['reference_number'],
                'contact_id' => $data['contact_id'] ?? null,
                'quotation_date' => $data['quotation_date'],
                'valid_until' => $data['valid_until'] ?? null,
                'status' => QuotationStatus::Draft,
                'subtotal_amount' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'] > 0 ? $totals['total'] : ($data['total_amount'] ?? 0),
                'currency_code' => $data['currency_code'] ?? config('accounting.base_currency', 'IQD'),
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
                'salesperson_id' => $data['salesperson_id'] ?? $user->id,
            ]);

            $this->syncLines($quotation, $totals['lines']);
            $this->events->log($quotation, 'created', $user, null, QuotationStatus::Draft->value);

            return $quotation->fresh(['lines', 'contact']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array<string, mixed>>  $lines
     */
    public function update(Quotation $quotation, User $user, array $data, array $lines = []): Quotation
    {
        abort_unless($user->can('quotations.update'), 403);
        $this->assertEditable($quotation);

        return DB::transaction(function () use ($quotation, $user, $data, $lines) {
            $totals = DocumentLineCalculator::summarize($lines);

            $quotation->update([
                'reference_number' => $data['reference_number'] ?? $quotation->reference_number,
                'contact_id' => $data['contact_id'] ?? $quotation->contact_id,
                'quotation_date' => $data['quotation_date'] ?? $quotation->quotation_date,
                'valid_until' => $data['valid_until'] ?? $quotation->valid_until,
                'subtotal_amount' => $totals['subtotal'],
                'discount_amount' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'total_amount' => $totals['total'] > 0 ? $totals['total'] : ($data['total_amount'] ?? $quotation->total_amount),
                'currency_code' => $data['currency_code'] ?? $quotation->currency_code,
                'notes' => $data['notes'] ?? $quotation->notes,
                'terms' => $data['terms'] ?? $quotation->terms,
            ]);

            $this->syncLines($quotation, $totals['lines']);
            $this->events->log($quotation, 'updated', $user);

            return $quotation->fresh(['lines', 'contact']);
        });
    }

    public function send(Quotation $quotation, User $user): Quotation
    {
        abort_unless($user->can('quotations.send') || $user->can('quotations.update'), 403);

        return $this->transition($quotation, $user, QuotationStatus::Sent, 'sent');
    }

    public function approve(Quotation $quotation, User $user, ?string $reason = null): Quotation
    {
        abort_unless($user->can('quotations.approve'), 403);

        return $this->transition($quotation, $user, QuotationStatus::Accepted, 'approved', $reason);
    }

    public function reject(Quotation $quotation, User $user, ?string $reason = null): Quotation
    {
        abort_unless($user->can('quotations.reject') || $user->can('quotations.approve'), 403);

        return $this->transition($quotation, $user, QuotationStatus::Rejected, 'rejected', $reason);
    }

    public function cancel(Quotation $quotation, User $user, ?string $reason = null): Quotation
    {
        abort_unless($user->can('quotations.update'), 403);

        return $this->transition($quotation, $user, QuotationStatus::Cancelled, 'cancelled', $reason);
    }

    public function expire(Quotation $quotation, User $user): Quotation
    {
        return $this->transition($quotation, $user, QuotationStatus::Expired, 'expired');
    }

    public function expireIfNeeded(Quotation $quotation, User $user): Quotation
    {
        if (
            $quotation->valid_until
            && $quotation->valid_until->isPast()
            && in_array($quotation->status, [QuotationStatus::Draft, QuotationStatus::Sent], true)
        ) {
            return $this->expire($quotation, $user);
        }

        return $quotation;
    }

    public function duplicate(Quotation $quotation, User $user): Quotation
    {
        abort_unless($user->can('quotations.create'), 403);

        $quotation->load('lines');

        return $this->create($user, [
            'reference_number' => $quotation->reference_number.'-COPY-'.now()->format('His'),
            'contact_id' => $quotation->contact_id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => $quotation->valid_until?->toDateString(),
            'currency_code' => $quotation->currency_code,
            'notes' => $quotation->notes,
            'terms' => $quotation->terms,
            'salesperson_id' => $user->id,
            'total_amount' => $quotation->total_amount,
        ], $quotation->lines->map(fn (QuotationLine $line) => [
            'product_id' => $line->product_id,
            'description' => $line->description,
            'quantity' => $line->quantity,
            'unit_price' => $line->unit_price,
            'discount_amount' => $line->discount_amount,
            'tax_amount' => $line->tax_amount,
        ])->all());
    }

    public function convertToSaleOrder(Quotation $quotation, User $user): SaleOrder
    {
        abort_unless($user->can('quotations.convert') || $user->can('quotations.approve'), 403);

        return DB::transaction(function () use ($quotation, $user) {
            $locked = Quotation::query()->whereKey($quotation->id)->lockForUpdate()->firstOrFail();
            $locked->load('lines');

            if ($locked->status === QuotationStatus::Converted || $locked->converted_sale_order_id) {
                throw ValidationException::withMessages([
                    'quotation' => [__('scf.sales_workflow.already_converted')],
                ]);
            }

            if ($locked->status !== QuotationStatus::Accepted) {
                throw ValidationException::withMessages([
                    'quotation' => [__('scf.sales_workflow.must_be_approved_to_convert')],
                ]);
            }

            if (! $locked->contact_id) {
                throw ValidationException::withMessages([
                    'contact_id' => [__('scf.sales_workflow.customer_required')],
                ]);
            }

            $order = SaleOrder::query()->create([
                'reference_number' => 'SO-'.str_replace(['QT-', 'Q-'], '', $locked->reference_number).'-'.now()->format('ymdHis'),
                'contact_id' => $locked->contact_id,
                'quotation_id' => $locked->id,
                'order_date' => now()->toDateString(),
                'status' => SaleOrderStatus::Draft,
                'subtotal_amount' => $locked->subtotal_amount,
                'discount_amount' => $locked->discount_amount,
                'tax_amount' => $locked->tax_amount,
                'total_amount' => $locked->total_amount,
                'currency_code' => $locked->currency_code,
                'notes' => $locked->notes,
                'terms' => $locked->terms,
                'salesperson_id' => $locked->salesperson_id ?? $user->id,
            ]);

            foreach ($locked->lines as $line) {
                SaleOrderLine::query()->create([
                    'sale_order_id' => $order->id,
                    'product_id' => $line->product_id,
                    'quotation_line_id' => $line->id,
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
                'status' => QuotationStatus::Converted,
                'converted_sale_order_id' => $order->id,
                'converted_at' => now(),
            ]);

            $this->events->log($locked, 'converted', $user, $from, QuotationStatus::Converted->value, null, (float) $locked->total_amount, $order);
            $this->events->log($order, 'created_from_quotation', $user, null, SaleOrderStatus::Draft->value, null, (float) $order->total_amount, $locked);

            return $order->fresh(['lines', 'contact', 'quotation']);
        });
    }

    protected function transition(
        Quotation $quotation,
        User $user,
        QuotationStatus $to,
        string $event,
        ?string $reason = null,
    ): Quotation {
        return DB::transaction(function () use ($quotation, $user, $to, $event, $reason) {
            $locked = Quotation::query()->whereKey($quotation->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo($to)) {
                throw ValidationException::withMessages([
                    'status' => [__('scf.sales_workflow.invalid_transition', [
                        'from' => $locked->status->label(),
                        'to' => $to->label(),
                    ])],
                ]);
            }

            $from = $locked->status->value;
            $locked->update(['status' => $to]);
            $this->events->log($locked, $event, $user, $from, $to->value, $reason, (float) $locked->total_amount);

            return $locked->fresh(['lines', 'contact', 'convertedSaleOrder']);
        });
    }

    protected function assertEditable(Quotation $quotation): void
    {
        if (! $quotation->status->isEditable()) {
            throw ValidationException::withMessages([
                'quotation' => [__('scf.sales_workflow.document_not_editable')],
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    protected function syncLines(Quotation $quotation, array $lines): void
    {
        $quotation->lines()->delete();

        foreach ($lines as $line) {
            if (trim((string) ($line['description'] ?? '')) === '' && empty($line['product_id'])) {
                continue;
            }

            $quotation->lines()->create($line);
        }
    }
}
