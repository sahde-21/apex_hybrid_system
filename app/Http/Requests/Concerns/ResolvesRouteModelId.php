<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Database\Eloquent\Model;

trait ResolvesRouteModelId
{
    /**
     * Resolve a route parameter to a primary key suitable for Rule::unique()->ignore().
     *
     * Implicit route model binding provides an Eloquent Model at runtime. PHPStan types
     * FormRequest::route() as object|string|null, so property access on ->id is unsafe.
     * This helper narrows Model / scalar route values without changing validation behavior.
     */
    protected function routeModelId(string $parameter): int|string|null
    {
        $value = $this->route($parameter);

        if ($value instanceof Model) {
            $key = $value->getKey();

            return is_int($key) || is_string($key) ? $key : null;
        }

        // Without model binding, Laravel may pass the raw route segment as a string.
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return null;
    }

    /**
     * Resolve a route parameter to an integer primary key for validation helpers.
     */
    protected function routeModelKey(string $parameter): ?int
    {
        $id = $this->routeModelId($parameter);

        if (is_int($id)) {
            return $id;
        }

        if (is_string($id) && ctype_digit($id)) {
            return (int) $id;
        }

        return null;
    }
}
