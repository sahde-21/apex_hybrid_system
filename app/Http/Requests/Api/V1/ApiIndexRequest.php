<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ApiIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $max = (int) config('api.pagination.max_per_page', 100);

        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.$max],
            'search' => ['sometimes', 'string', 'max:255'],
            'sort' => ['sometimes', 'string', 'max:64'],
            'include' => ['sometimes', 'string', 'max:500'],
            'status' => ['sometimes', 'string', 'max:64'],
            'customer_id' => ['sometimes', 'integer', 'exists:contacts,id'],
            'supplier_id' => ['sometimes', 'integer', 'exists:contacts,id'],
            'contact_id' => ['sometimes', 'integer', 'exists:contacts,id'],
            'branch_id' => ['sometimes', 'integer'],
            'warehouse_id' => ['sometimes', 'integer'],
            'currency_code' => ['sometimes', 'string', 'max:3'],
            'created_by' => ['sometimes', 'integer', 'exists:users,id'],
            'created_from' => ['sometimes', 'date'],
            'created_to' => ['sometimes', 'date'],
            'updated_from' => ['sometimes', 'date'],
            'updated_to' => ['sometimes', 'date'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date'],
            'date_field' => ['sometimes', 'string', 'max:64'],
            'type' => ['sometimes', 'string', 'max:64'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
