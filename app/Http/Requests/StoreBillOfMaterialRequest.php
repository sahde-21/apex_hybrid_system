<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBillOfMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('bill-of-materials.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'component_product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric'],
            'unit' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
