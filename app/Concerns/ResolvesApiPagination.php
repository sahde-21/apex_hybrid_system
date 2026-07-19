<?php

namespace App\Concerns;

use Illuminate\Http\Request;

trait ResolvesApiPagination
{
    protected function perPage(Request $request): int
    {
        $default = (int) config('api.pagination.per_page', 15);
        $max = (int) config('api.pagination.max_per_page', 100);
        $requested = (int) $request->integer('per_page', $default);

        return max(1, min($requested, $max));
    }
}
