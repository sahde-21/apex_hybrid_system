<?php

namespace App\Support\Inventory;

readonly class PurchaseLedgerPreflightResult
{
    /**
     * @param  list<string>  $failures
     * @param  list<string>  $warnings
     */
    public function __construct(
        public bool $passed,
        public bool $ledgerEnabled,
        public int $receivableOrdersMissingWarehouse,
        public int $openOrdersWithPartialReceipts,
        public int $receiptCount,
        public int $returnCount,
        public array $failures,
        public array $warnings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'passed' => $this->passed,
            'ledger_enabled' => $this->ledgerEnabled,
            'receivable_orders_missing_warehouse' => $this->receivableOrdersMissingWarehouse,
            'open_orders_with_partial_receipts' => $this->openOrdersWithPartialReceipts,
            'receipt_count' => $this->receiptCount,
            'return_count' => $this->returnCount,
            'failures' => $this->failures,
            'warnings' => $this->warnings,
        ];
    }
}
