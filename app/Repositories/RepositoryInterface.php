<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
interface RepositoryInterface
{
    /** @return class-string<TModel> */
    public function setModel(): string;

    /** @return Collection<int, TModel> */
    public function all(): Collection;

    /** @return Collection<int, TModel> */
    public function allWith(array $relations = []): Collection;

    /** @return Collection<int, TModel> */
    public function allWhere(string $column, mixed $value): Collection;

    /** @return Collection<int, TModel> */
    public function allWhereLike(string $column, mixed $value): Collection;

    /** @return Collection<int, TModel> */
    public function allBy(string $column, string $sort = 'asc'): Collection;

    /** @return LengthAwarePaginator<int, TModel> */
    public function paginateAll(int $limit = 8): LengthAwarePaginator;

    /** @return LengthAwarePaginator<int, TModel> */
    public function paginateAllWith(int $limit = 8, array $relations = []): LengthAwarePaginator;

    /** @return TModel|null */
    public function find(int|string $id, array $relations = []): ?Model;

    /** @return TModel */
    public function firstBy(string $column, mixed $value, array $relations = []): Model;

    /**
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function store(array $data, ?string $model = null): Model;

    /** @return TModel */
    public function update(int|string $id, array $data): Model;

    public function delete(int|string $id): bool;
}
