<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShippingMethodRequest extends FormRequest
{
    use ResolvesRouteModelId;

    public function authorize(): bool
    {
        return $this->user()?->can('shipping-methods.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->routeModelId('shippingMethod');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('shipping_methods', 'code')->ignore($id)],
            'carrier' => ['nullable', 'string', 'max:255'],
            'base_cost' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
