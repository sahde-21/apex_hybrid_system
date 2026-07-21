<?php

namespace App\Http\Requests\Api\V1\Concerns;

trait DocumentLineRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function documentLineRules(bool $required = true): array
    {
        $lineRules = $required ? ['required', 'array', 'min:1'] : ['sometimes', 'array'];

        return [
            'lines' => $lineRules,
            'lines.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'lines.*.description' => ['nullable', 'string', 'max:2000'],
            'lines.*.quantity' => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.unit_price' => ['required_with:lines', 'numeric', 'min:0'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
