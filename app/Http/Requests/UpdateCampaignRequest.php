<?php

namespace App\Http\Requests;

use App\Enums\CampaignStatus;
use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaignRequest extends FormRequest
{
    use ResolvesRouteModelId;

    public function authorize(): bool
    {
        return $this->user()?->can('campaigns.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->routeModelId('campaign');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('campaigns', 'code')->ignore($id)],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'budget' => ['nullable', 'numeric'],
            'status' => ['nullable', Rule::enum(CampaignStatus::class)],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
