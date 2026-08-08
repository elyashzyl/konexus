<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

/**
 * @extends BaseRepository<User>
 *
 * @implements UserRepositoryInterface
 */
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * Resolve the model class name.
     *
     * @return class-string<User>
     */
    protected function model(): string
    {
        return User::class;
    }
}
