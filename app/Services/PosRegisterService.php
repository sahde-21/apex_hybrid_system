<?php

namespace App\Services;

use App\Repositories\PosRegisterRepository;

class PosRegisterService extends BaseService
{
    public function __construct(PosRegisterRepository $repository)
    {
        parent::__construct($repository);
    }
}
