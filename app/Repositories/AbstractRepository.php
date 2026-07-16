<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractRepository
{
    abstract public function setModel(): string;

    /** @return Builder<Model> */
    protected function query(): Builder
    {
        return ($this->setModel())::query();
    }

    public function count(): int
    {
        return $this->query()->count();
    }
}
