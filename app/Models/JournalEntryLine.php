<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $journal_entry_id
 * @property int $account_id
 * @property int $line_number
 * @property string|null $description
 * @property string $debit
 * @property string $credit
 * @property string|null $currency_code
 * @property string $exchange_rate
 * @property string $base_debit
 * @property string $base_credit
 * @property int|null $contact_id
 * @property int|null $branch_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read JournalEntry $journalEntry
 * @property-read Account $account
 * @property-read Contact|null $contact
 * @property-read Branch|null $branch
 * @property string|null $running_balance
 * @property string|null $balance
 */
#[Fillable([
    'journal_entry_id',
    'account_id',
    'line_number',
    'description',
    'debit',
    'credit',
    'currency_code',
    'exchange_rate',
    'base_debit',
    'base_credit',
    'contact_id',
    'branch_id',
])]
class JournalEntryLine extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'exchange_rate' => 'decimal:8',
            'base_debit' => 'decimal:2',
            'base_credit' => 'decimal:2',
            'line_number' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
