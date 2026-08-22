<?php

namespace App\Http\Requests\Api\V1;

use App\Concerns\BranchValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    use BranchValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('branches.create') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return $this->apiBranchRules();
    }
}
