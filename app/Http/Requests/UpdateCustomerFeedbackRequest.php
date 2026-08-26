<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerFeedbackRequest extends FormRequest
{
    use ResolvesRouteModelId;

    public function authorize(): bool
    {
        return $this->user()?->can('customer-feedback.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->routeModelId('customerFeedback');

        return [
            'contact_id' => ['required', 'exists:contacts,id'],
            'rating' => ['required', 'integer'],
            'subject' => ['required', 'string', 'max:255'],
            'feedback' => ['required', 'string', 'max:5000'],
            'feedback_date' => ['required', 'date'],
        ];
    }
}
