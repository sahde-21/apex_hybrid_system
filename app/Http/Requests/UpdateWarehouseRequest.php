<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends FormRequest
{
    use ResolvesRouteModelId;

    public function authorize(): bool
    {
        return $this->user()?->can('warehouses.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->routeModelId('warehouse');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('warehouses', 'code')->ignore($id)],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
