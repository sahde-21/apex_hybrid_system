<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\DocumentLineRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuotationRequest extends FormRequest
{
    use DocumentLineRules;

    public function authorize(): bool
    {
        return $this->user()?->can('quotations.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge([
            'reference_number' => ['required', 'string', 'max:100', Rule::unique('quotations', 'reference_number')],
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'quotation_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date'],
            'currency_code' => ['nullable', 'string', 'max:3'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms' => ['nullable', 'string', 'max:2000'],
        ], $this->documentLineRules());
    }
}
