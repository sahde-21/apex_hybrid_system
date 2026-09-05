<?php

namespace App\Http\Requests;

use App\Enums\SubscriptionStatus;
use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionRequest extends FormRequest
{
    use ResolvesRouteModelId;

    public function authorize(): bool
    {
        return $this->user()?->can('subscriptions.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->routeModelId('subscription');

        return [
            'contact_id' => ['required', 'exists:contacts,id'],
            'plan_name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric'],
            'billing_cycle' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(SubscriptionStatus::class)],
        ];
    }
}
