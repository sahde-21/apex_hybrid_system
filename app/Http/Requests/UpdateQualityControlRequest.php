<?php

namespace App\Http\Requests;

use App\Enums\QualityControlStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQualityControlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('quality-control.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->route('qualityControl')?->id;

        return [
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('quality_controls', 'reference_number')->ignore($id)],
            'production_order_id' => ['nullable', 'exists:production_orders,id'],
            'product_id' => ['required', 'exists:products,id'],
            'inspection_date' => ['required', 'date'],
            'status' => ['nullable', Rule::enum(QualityControlStatus::class)],
            'passed_quantity' => ['nullable', 'integer'],
            'failed_quantity' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
