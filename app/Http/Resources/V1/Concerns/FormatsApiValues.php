<?php

namespace App\Http\Resources\V1\Concerns;

use BackedEnum;
use Carbon\CarbonInterface;
use DateTimeInterface;

trait FormatsApiValues
{
    /**
     * Format a monetary amount for the API.
     *
     * Runtime amounts are typically Eloquent decimal casts (string) or numeric inputs.
     * The serialized amount is always a fixed 2-decimal string.
     *
     * @return array{amount: string, currency_code: string|null}
     */
    protected function money(mixed $amount, ?string $currency = null): array
    {
        return [
            'amount' => number_format((float) $amount, 2, '.', ''),
            'currency_code' => $currency,
        ];
    }

    /**
     * Format a date-time as an ISO-8601 string.
     *
     * Eloquent datetime casts yield Carbon instances; null stays null.
     * Mixed is accepted because some resources read optional attributes via getAttribute().
     */
    protected function isoDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->toIso8601String();
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if (is_string($value)) {
            return $value;
        }

        return null;
    }

    /**
     * Format a calendar date as Y-m-d.
     *
     * Eloquent date casts yield Carbon instances; null stays null.
     */
    protected function dateOnly(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value)) {
            return $value;
        }

        return null;
    }

    /**
     * Format a backed enum (or raw string fallback) as {value, label}.
     *
     * Domain enums expose label(); when absent, the backed value is reused as the label.
     * The string branch exists for static-analysis cases where cast enums are still
     * inferred as strings — at runtime Eloquent enum casts pass BackedEnum instances.
     *
     * @return array{value: string, label: string}|null
     */
    protected function enumValue(BackedEnum|string|null $enum): ?array
    {
        if ($enum === null) {
            return null;
        }

        if ($enum instanceof BackedEnum) {
            $value = (string) $enum->value;
            $label = method_exists($enum, 'label') ? (string) $enum->label() : $value;

            return [
                'value' => $value,
                'label' => $label,
            ];
        }

        return [
            'value' => $enum,
            'label' => $enum,
        ];
    }
}
