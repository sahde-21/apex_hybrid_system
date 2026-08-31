<?php

namespace App\Services;

use App\Repositories\VariantRepository;
use App\Models\Variant;

/**
 * @extends BaseService<Variant>
 */
class VariantService extends BaseService
{
    public function __construct(VariantRepository $repository)
    {
        parent::__construct($repository);
    }
}
