<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('customer-feedback.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'contact_id' => ['required', 'exists:contacts,id'],
            'rating' => ['required', 'integer'],
            'subject' => ['required', 'string', 'max:255'],
            'feedback' => ['required', 'string', 'max:5000'],
            'feedback_date' => ['required', 'date'],
        ];
    }
}
