<?php

namespace App\Services;

use App\Repositories\VariantRepository;

class VariantService extends BaseService
{
    public function __construct(VariantRepository $repository)
    {
        parent::__construct($repository);
    }
}
