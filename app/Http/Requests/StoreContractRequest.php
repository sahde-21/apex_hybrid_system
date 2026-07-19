<?php

namespace App\Http\Requests;

use App\Enums\ContractStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('contracts.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('contracts', 'reference_number')],
            'contact_id' => ['required', 'exists:contacts,id'],
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'value' => ['nullable', 'numeric'],
            'status' => ['nullable', Rule::enum(ContractStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
