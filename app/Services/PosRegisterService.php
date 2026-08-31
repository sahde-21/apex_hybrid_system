<?php

namespace App\Services;

use App\Repositories\PosRegisterRepository;
use App\Models\PosRegister;

/**
 * @extends BaseService<PosRegister>
 */
class PosRegisterService extends BaseService
{
    public function __construct(PosRegisterRepository $repository)
    {
        parent::__construct($repository);
    }
}
