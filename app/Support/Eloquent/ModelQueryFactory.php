<?php

namespace App\Support\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class ModelQueryFactory
{
    /**
     * @param  class-string<Model>  $modelClass
     * @return Builder<Model>
     */
    public static function queryFor(string $modelClass): Builder
    {
        return $modelClass::query();
    }
}
