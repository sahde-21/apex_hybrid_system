<?php

namespace App\Concerns;

use App\Enums\JournalEntryStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait JournalEntryValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function journalEntryRules(?int $journalEntryId = null): array
    {
        return [
            'reference_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('journal_entries', 'reference_number')->ignore($journalEntryId),
            ],
            'entry_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(JournalEntryStatus::class)],
            'total_debit' => ['required', 'numeric', 'min:0'],
            'total_credit' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
