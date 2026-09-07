<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFixedAssetRequest extends FormRequest
{
    use ResolvesRouteModelId;

    public function authorize(): bool
    {
        return $this->user()?->can('fixed-assets.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->routeModelId('fixedAsset');

        return [
            'name' => ['required', 'string', 'max:255'],
            'asset_code' => ['required', 'string', 'max:255', Rule::unique('fixed_assets', 'asset_code')->ignore($id)],
            'purchase_date' => ['required', 'date'],
            'purchase_cost' => ['required', 'numeric'],
            'current_value' => ['nullable', 'numeric'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
