<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\RecordNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractRepository
{
    abstract public function setModel(): string;

    /** @return Builder<Model> */
    protected function query(): Builder
    {
        return ($this->setModel())::query();
    }

    /** @return Collection<int, Model> */
    public function all(): Collection
    {
        return $this->query()->get();
    }

    /** @return Collection<int, Model> */
    public function allWith(array $relations = []): Collection
    {
        return $this->query()->with($relations)->get();
    }

    /** @return Collection<int, Model> */
    public function allWhere(string $column, mixed $value): Collection
    {
        return $this->query()->where($column, $value)->get();
    }

    /** @return Collection<int, Model> */
    public function allWhereLike(string $column, mixed $value): Collection
    {
        return $this->query()->where($column, 'like', "%{$value}%")->get();
    }

    /** @return Collection<int, Model> */
    public function allBy(string $column, string $sort = 'asc'): Collection
    {
        return $this->query()->orderBy($column, $sort)->get();
    }

    /** @return LengthAwarePaginator<int, Model> */
    public function paginateAll(int $limit = 8): LengthAwarePaginator
    {
        return $this->query()->paginate($limit);
    }

    /** @return LengthAwarePaginator<int, Model> */
    public function paginateAllWith(int $limit = 8, array $relations = []): LengthAwarePaginator
    {
        return $this->query()->with($relations)->paginate($limit);
    }

    public function find(int|string $id, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function firstBy(string $column, mixed $value, array $relations = []): Model
    {
        return $this->query()->with($relations)->where($column, $value)->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data, ?string $model = null): Model
    {
        $instance = $model ? new $model : $this->query()->getModel()->newInstance();
        $instance->fill($data);
        $instance->save();

        return $instance;
    }

    public function update(int|string $id, array $data): Model
    {
        $model = $this->find($id);

        if (!$model) {
            throw new RecordNotFoundException("Record [{$id}] not found.");
        }

        $model->update($data);

        return $model;
    }

    public function delete(int|string $id): bool
    {
        $model = $this->find($id);

        if (!$model) {
            throw new RecordNotFoundException("Record [{$id}] not found.");
        }

        return (bool) $model->delete();
    }
}
