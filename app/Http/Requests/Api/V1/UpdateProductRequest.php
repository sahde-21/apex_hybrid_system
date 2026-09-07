<?php

namespace App\Http\Requests\Api\V1;

use App\Concerns\ProductValidationRules;
use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    use ProductValidationRules;
    use ResolvesRouteModelId;

    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product && $this->user()?->can('update', $product);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->productRules($this->routeModelKey('product')), [
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:product_categories,id'],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'is_favorite' => ['sometimes', 'boolean'],
        ]);
    }
}
