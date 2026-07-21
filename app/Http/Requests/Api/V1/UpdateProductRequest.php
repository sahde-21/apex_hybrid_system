<?php

namespace App\Http\Requests\Api\V1;

use App\Concerns\ProductValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    use ProductValidationRules;

    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product && $this->user()?->can('update', $product);
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return array_merge($this->productRules($product?->id), [
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:product_categories,id'],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'is_favorite' => ['sometimes', 'boolean'],
        ]);
    }
}
