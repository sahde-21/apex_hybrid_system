<?php

namespace App\Repositories;

use App\Models\User;

/**
 * @extends BaseRepository<User>
 */
class UserRepository extends BaseRepository
{
    protected string $model = User::class;
}
