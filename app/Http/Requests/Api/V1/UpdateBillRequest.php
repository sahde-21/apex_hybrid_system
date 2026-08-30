<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\DocumentLineRules;
use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBillRequest extends FormRequest
{
    use DocumentLineRules;
    use ResolvesRouteModelId;

    public function authorize(): bool
    {
        $bill = $this->route('bill');

        return $bill && $this->user()?->can('update', $bill);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge([
            'reference_number' => ['sometimes', 'string', 'max:100', Rule::unique('bills', 'reference_number')->ignore($this->routeModelId('bill'))],
            'contact_id' => ['sometimes', 'nullable', 'integer', 'exists:contacts,id'],
            'purchase_order_id' => ['sometimes', 'nullable', 'integer', 'exists:purchase_orders,id'],
            'bill_date' => ['sometimes', 'date'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'currency_code' => ['sometimes', 'nullable', 'string', 'max:3'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ], $this->documentLineRules(false));
    }
}
