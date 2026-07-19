<?php

namespace App\Http\Requests;

use App\Enums\JournalEntryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('journal-entries.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:100', Rule::unique('journal_entries', 'reference_number')],
            'entry_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(JournalEntryStatus::class)],
            'total_debit' => ['required', 'numeric', 'min:0'],
            'total_credit' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
