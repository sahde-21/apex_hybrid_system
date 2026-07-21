<?php

namespace App\Jobs;

use App\Enums\InvoiceStatus;
use App\Models\Bill;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Rfq;
use App\Enums\BillStatus;
use App\Enums\QuotationStatus;
use App\Enums\RfqStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MaintainDocumentStatusesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        if (config('performance.scheduler.overdue_documents', true)) {
            Invoice::query()
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->whereIn('status', [InvoiceStatus::Sent->value, InvoiceStatus::PartiallyPaid->value])
                ->chunkById(100, function ($invoices): void {
                    foreach ($invoices as $invoice) {
                        if ((float) $invoice->paid_amount + 0.001 >= (float) $invoice->total_amount) {
                            continue;
                        }
                        $invoice->update(['status' => InvoiceStatus::Overdue]);
                    }
                });

            Bill::query()
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->whereIn('status', [BillStatus::Received->value, BillStatus::PartiallyPaid->value])
                ->chunkById(100, function ($bills): void {
                    foreach ($bills as $bill) {
                        if ((float) $bill->paid_amount + 0.001 >= (float) $bill->total_amount) {
                            continue;
                        }
                        $bill->update(['status' => BillStatus::Overdue]);
                    }
                });
        }

        if (config('performance.scheduler.expire_documents', true)) {
            Quotation::query()
                ->whereNotNull('valid_until')
                ->whereDate('valid_until', '<', now()->toDateString())
                ->whereIn('status', [QuotationStatus::Draft->value, QuotationStatus::Sent->value])
                ->chunkById(100, fn ($rows) => $rows->each(fn (Quotation $q) => $q->update(['status' => QuotationStatus::Expired])));

            Rfq::query()
                ->whereNotNull('valid_until')
                ->whereDate('valid_until', '<', now()->toDateString())
                ->whereIn('status', [RfqStatus::Draft->value, RfqStatus::Sent->value])
                ->chunkById(100, fn ($rows) => $rows->each(fn (Rfq $rfq) => $rfq->update(['status' => RfqStatus::Expired])));
        }
    }
}
