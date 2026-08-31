<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use App\Models\Product;

/**
 * @extends BaseService<Product>
 */
class ProductService extends BaseService
{
    public function __construct(ProductRepository $repository)
    {
        parent::__construct($repository);
    }
}
