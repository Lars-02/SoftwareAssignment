<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;

/** @implements RepositoryInterface<User> */
class UserRepository extends AbstractRepository implements RepositoryInterface
{
    public function setModel(): string
    {
        return User::class;
    }
}
