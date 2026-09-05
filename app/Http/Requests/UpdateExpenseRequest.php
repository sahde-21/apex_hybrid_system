<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    use ResolvesRouteModelId;

    public function authorize(): bool
    {
        return $this->user()?->can('expenses.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->routeModelId('expense');

        return [
            'reference_number' => ['required', 'string', 'max:100', Rule::unique('expenses', 'reference_number')->ignore($id)],
            'contact_id' => ['nullable', 'exists:contacts,id'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'expense_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
