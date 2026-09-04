<?php

namespace App\Support\Database;

final class SchemaIndexHelper
{
    /**
     * @return list<string>
     */
    public static function listing(object $schemaBuilder, string $table): array
    {
        if (! method_exists($schemaBuilder, 'getIndexListing')) {
            return [];
        }

        $listing = $schemaBuilder->getIndexListing($table);

        return is_array($listing) ? array_values($listing) : [];
    }
}
