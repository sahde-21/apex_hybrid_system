<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\DocumentLineRules;
use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRfqRequest extends FormRequest
{
    use DocumentLineRules;
    use ResolvesRouteModelId;

    public function authorize(): bool
    {
        $rfq = $this->route('rfq');

        return $rfq && $this->user()?->can('update', $rfq);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge([
            'reference_number' => ['sometimes', 'string', 'max:100', Rule::unique('rfqs', 'reference_number')->ignore($this->routeModelId('rfq'))],
            'purchase_request_id' => ['sometimes', 'nullable', 'integer', 'exists:purchase_requests,id'],
            'rfq_date' => ['sometimes', 'date'],
            'valid_until' => ['sometimes', 'nullable', 'date'],
            'currency_code' => ['sometimes', 'nullable', 'string', 'max:3'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'terms' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'vendor_ids' => ['sometimes', 'nullable', 'array'],
            'vendor_ids.*' => ['integer', 'exists:contacts,id'],
        ], $this->documentLineRules(false));
    }
}
