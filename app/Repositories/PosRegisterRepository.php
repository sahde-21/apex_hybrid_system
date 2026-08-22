<?php

namespace App\Repositories;

use App\Models\PosRegister;

/**
 * @extends BaseRepository<PosRegister>
 */
class PosRegisterRepository extends BaseRepository
{
    protected string $model = PosRegister::class;
}
