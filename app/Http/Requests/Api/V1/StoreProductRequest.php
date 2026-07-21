<?php

namespace App\Http\Requests\Api\V1;

use App\Concerns\ProductValidationRules;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    use ProductValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('products.create') ?? false;
    }

    public function rules(): array
    {
        return array_merge($this->productRules(), [
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'is_favorite' => ['sometimes', 'boolean'],
        ]);
    }
}
