<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('supplier-evaluations.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->route('supplierEvaluation')?->id;

        return [
            'contact_id' => ['required', 'exists:contacts,id'],
            'evaluation_date' => ['required', 'date'],
            'quality_score' => ['nullable', 'integer'],
            'delivery_score' => ['nullable', 'integer'],
            'price_score' => ['nullable', 'integer'],
            'overall_score' => ['nullable', 'integer'],
            'comments' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
