<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
interface RepositoryInterface
{
    /** @return class-string<TModel> */
    public function setModel(): string;

    public function count(): int;
}
