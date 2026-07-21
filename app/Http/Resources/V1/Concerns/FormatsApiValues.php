<?php

namespace App\Http\Resources\V1\Concerns;

trait FormatsApiValues
{
    protected function money(mixed $amount, ?string $currency = null): array
    {
        return [
            'amount' => number_format((float) $amount, 2, '.', ''),
            'currency_code' => $currency,
        ];
    }

    protected function isoDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value->toIso8601String();
    }

    protected function dateOnly(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value->format('Y-m-d');
    }

    /**
     * @return array{value: string, label: string}
     */
    protected function enumValue(?object $enum): ?array
    {
        if ($enum === null) {
            return null;
        }

        return [
            'value' => $enum->value,
            'label' => method_exists($enum, 'label') ? $enum->label() : $enum->value,
        ];
    }
}
