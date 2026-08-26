<?php

namespace App\Http\Requests;

use App\Enums\BankReconciliationStatus;
use App\Http\Requests\Concerns\ResolvesRouteModelId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBankReconciliationRequest extends FormRequest
{
    use ResolvesRouteModelId;

    public function authorize(): bool
    {
        return $this->user()?->can('bank-reconciliation.update') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->routeModelId('bankReconciliation');

        return [
            'reference_number' => ['required', 'string', 'max:255', Rule::unique('bank_reconciliations', 'reference_number')->ignore($id)],
            'bank_name' => ['required', 'string', 'max:255'],
            'statement_date' => ['required', 'date'],
            'opening_balance' => ['nullable', 'numeric'],
            'closing_balance' => ['nullable', 'numeric'],
            'status' => ['nullable', Rule::enum(BankReconciliationStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
