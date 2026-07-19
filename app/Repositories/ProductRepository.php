<?php

namespace App\Repositories;

use App\Models\Product;

/**
 * @extends BaseRepository<Product>
 */
class ProductRepository extends BaseRepository
{
    protected string $model = Product::class;
}
