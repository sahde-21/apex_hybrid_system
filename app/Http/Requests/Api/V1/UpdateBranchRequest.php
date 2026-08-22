<?php

namespace App\Http\Requests\Api\V1;

use App\Concerns\BranchValidationRules;
use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
{
    use BranchValidationRules;

    public function authorize(): bool
    {
        $branch = $this->route('branch');

        return $branch instanceof Branch
            && ($this->user()?->can('update', $branch) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $branch = $this->route('branch');

        return $this->apiBranchRules($branch instanceof Branch ? $branch->id : null);
    }
}
