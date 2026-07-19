<?php

namespace App\Concerns;

use App\Enums\CampaignStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait CampaignValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function campaignRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('campaigns', 'code')],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'budget' => ['nullable', 'numeric'],
            'status' => ['nullable', Rule::enum(CampaignStatus::class)],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function campaignUpdateRules(?int $campaignId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', Rule::unique('campaigns', 'code')->ignore($campaignId)],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'budget' => ['nullable', 'numeric'],
            'status' => ['nullable', Rule::enum(CampaignStatus::class)],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
