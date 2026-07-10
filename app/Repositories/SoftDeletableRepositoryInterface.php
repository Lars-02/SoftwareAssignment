<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @extends RepositoryInterface<TModel>
 */
interface SoftDeletableRepositoryInterface extends RepositoryInterface
{
    /** @return Collection<int, TModel> */
    public function trashed(): Collection;

    /** @return Collection<int, TModel> */
    public function allWithTrashed(): Collection;

    /** @return LengthAwarePaginator<int, TModel> */
    public function paginateTrashed(int $limit = 8): LengthAwarePaginator;

    /** @return TModel|null */
    public function findWithTrashed(int|string $id, array $relations = []): ?Model;

    public function restore(int|string $id): bool;

    public function forceDelete(int|string $id): bool;
}
