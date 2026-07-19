<?php

namespace App\Observers;

use App\Models\Bill;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Payroll;
use App\Services\Accounting\AutoPostingService;
use Illuminate\Support\Facades\Log;

class AccountingDocumentObserver
{
    public function __construct(
        protected AutoPostingService $posting,
    ) {}

    public function created(Invoice|Bill|Payment|Expense|Payroll $model): void
    {
        $this->dispatch($model);
    }

    public function updated(Invoice|Bill|Payment|Expense|Payroll $model): void
    {
        $this->dispatch($model);
    }

    protected function dispatch(Invoice|Bill|Payment|Expense|Payroll $model): void
    {
        if (! config('accounting.auto_post', true)) {
            return;
        }

        try {
            match (true) {
                $model instanceof Invoice => $this->posting->postInvoice($model),
                $model instanceof Bill => $this->posting->postBill($model),
                $model instanceof Payment => $model->type === \App\Enums\PaymentType::Incoming
                    ? $this->posting->postCustomerPayment($model)
                    : $this->posting->postSupplierPayment($model),
                $model instanceof Expense => $this->posting->postExpense($model),
                $model instanceof Payroll => $this->posting->postPayroll($model),
            };
        } catch (\Throwable $e) {
            Log::warning('accounting.auto_post_failed', [
                'model' => $model::class,
                'id' => $model->getKey(),
                'message' => $e->getMessage(),
            ]);
        }
    }
}
