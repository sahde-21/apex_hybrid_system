<?php

namespace App\Support\Bi;

use BackedEnum;
use DateTimeInterface;

final class BiValueFormatter
{
    public static function formatDate(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value) && $value !== '') {
            return $value;
        }

        return null;
    }

    public static function enumValue(mixed $value, string $fallback = '—'): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        if ($value === null || $value === '') {
            return $fallback;
        }

        return (string) $value;
    }

    /**
     * @param  iterable<int, array<int, mixed>>  $rows
     * @return list<list<mixed>>
     */
    public static function listRows(iterable $rows): array
    {
        return array_values(array_map(
            static fn (array $row): array => array_values($row),
            is_array($rows) ? $rows : iterator_to_array($rows),
        ));
    }

    /**
     * @param  iterable<int|string, mixed>  $labels
     * @return list<string>
     */
    public static function listLabels(iterable $labels): array
    {
        return array_values(array_map(
            static fn (mixed $label): string => (string) $label,
            is_array($labels) ? $labels : iterator_to_array($labels),
        ));
    }
}
