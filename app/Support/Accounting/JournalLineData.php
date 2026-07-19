<?php

namespace App\Support\Accounting;

final class JournalLineData
{
    public function __construct(
        public int $accountId,
        public string $debit,
        public string $credit,
        public ?string $description = null,
        public ?int $contactId = null,
        public ?int $branchId = null,
        public string $currencyCode = 'IQD',
        public string $exchangeRate = '1',
    ) {}

    /**
     * @param  array{
     *     account_id: int,
     *     debit?: float|int|string,
     *     credit?: float|int|string,
     *     description?: string|null,
     *     contact_id?: int|null,
     *     branch_id?: int|null,
     *     currency_code?: string|null,
     *     exchange_rate?: float|int|string|null
     * }  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            accountId: (int) $data['account_id'],
            debit: number_format((float) ($data['debit'] ?? 0), 2, '.', ''),
            credit: number_format((float) ($data['credit'] ?? 0), 2, '.', ''),
            description: $data['description'] ?? null,
            contactId: isset($data['contact_id']) ? (int) $data['contact_id'] : null,
            branchId: isset($data['branch_id']) ? (int) $data['branch_id'] : null,
            currencyCode: (string) ($data['currency_code'] ?? 'IQD'),
            exchangeRate: number_format((float) ($data['exchange_rate'] ?? 1), 8, '.', ''),
        );
    }
}
