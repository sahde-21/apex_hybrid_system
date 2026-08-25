<?php

namespace App\Support\Inventory;

readonly class AdjustmentTransferPreflightResult
{
    /**
     * @param  list<string>  $failures
     * @param  list<string>  $warnings
     */
    public function __construct(
        public bool $passed,
        public bool $ledgerEnabled,
        public int $adjustmentsMissingWarehouse,
        public int $adjustmentsInvalidReason,
        public int $transfersSameWarehouse,
        public int $inTransitCount,
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
            'adjustments_missing_warehouse' => $this->adjustmentsMissingWarehouse,
            'adjustments_invalid_reason' => $this->adjustmentsInvalidReason,
            'transfers_same_warehouse' => $this->transfersSameWarehouse,
            'in_transit_count' => $this->inTransitCount,
            'failures' => $this->failures,
            'warnings' => $this->warnings,
        ];
    }
}
