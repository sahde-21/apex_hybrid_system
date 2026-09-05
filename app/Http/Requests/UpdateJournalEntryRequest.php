<?php

namespace App\Http\Requests;

use App\Enums\JournalEntryStatus;
use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJournalEntryRequest extends FormRequest
{
    use ResolvesRouteModelId;

    public function authorize(): bool
    {
        return $this->user()?->can('journal-entries.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->routeModelId('journalEntry');

        return [
            'reference_number' => ['required', 'string', 'max:100', Rule::unique('journal_entries', 'reference_number')->ignore($id)],
            'entry_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(JournalEntryStatus::class)],
            'total_debit' => ['required', 'numeric', 'min:0'],
            'total_credit' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
