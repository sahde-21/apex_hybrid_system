<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\V1\Concerns\DocumentLineRules;
use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSaleOrderRequest extends FormRequest
{
    use DocumentLineRules;
    use ResolvesRouteModelId;

    public function authorize(): bool
    {
        $saleOrder = $this->route('sale_order');

        return $saleOrder && $this->user()?->can('update', $saleOrder);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge([
            'reference_number' => ['sometimes', 'string', 'max:100', Rule::unique('sale_orders', 'reference_number')->ignore($this->routeModelId('sale_order'))],
            'contact_id' => ['sometimes', 'nullable', 'integer', 'exists:contacts,id'],
            'quotation_id' => ['sometimes', 'nullable', 'integer', 'exists:quotations,id'],
            'order_date' => ['sometimes', 'date'],
            'delivery_date' => ['sometimes', 'nullable', 'date'],
            'currency_code' => ['sometimes', 'nullable', 'string', 'max:3'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'terms' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ], $this->documentLineRules(false));
    }
}
