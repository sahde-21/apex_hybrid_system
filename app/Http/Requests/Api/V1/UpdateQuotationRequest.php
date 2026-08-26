<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\DocumentLineRules;
use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuotationRequest extends FormRequest
{
    use DocumentLineRules;
    use ResolvesRouteModelId;

    public function authorize(): bool
    {
        $quotation = $this->route('quotation');

        return $quotation && $this->user()?->can('update', $quotation);
    }

    public function rules(): array
    {
        return array_merge([
            'reference_number' => ['sometimes', 'string', 'max:100', Rule::unique('quotations', 'reference_number')->ignore($this->routeModelId('quotation'))],
            'contact_id' => ['sometimes', 'nullable', 'integer', 'exists:contacts,id'],
            'quotation_date' => ['sometimes', 'date'],
            'valid_until' => ['sometimes', 'nullable', 'date'],
            'currency_code' => ['sometimes', 'nullable', 'string', 'max:3'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'terms' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ], $this->documentLineRules(false));
    }
}
