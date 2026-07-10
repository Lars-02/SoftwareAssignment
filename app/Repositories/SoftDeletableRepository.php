<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\RecordNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class SoftDeletableRepository extends AbstractRepository
{
    /** @return Collection<int, Model> */
    public function trashed(): Collection
    {
        return $this->query()->onlyTrashed()->get();
    }

    /** @return Collection<int, Model> */
    public function allWithTrashed(): Collection
    {
        return $this->query()->withTrashed()->get();
    }

    /** @return LengthAwarePaginator<int, Model> */
    public function paginateTrashed(int $limit = 8): LengthAwarePaginator
    {
        return $this->query()->onlyTrashed()->paginate($limit);
    }

    public function findWithTrashed(int|string $id, array $relations = []): ?Model
    {
        return $this->query()->withTrashed()->with($relations)->find($id);
    }

    public function restore(int|string $id): bool
    {
        $model = $this->query()->withTrashed()->find($id);

        if (!$model) {
            throw new RecordNotFoundException("Record [{$id}] not found.");
        }

        return (bool) $model->restore();
    }

    public function forceDelete(int|string $id): bool
    {
        $model = $this->query()->withTrashed()->find($id);

        if (!$model) {
            throw new RecordNotFoundException("Record [{$id}] not found.");
        }

        return (bool) $model->forceDelete();
    }
}
