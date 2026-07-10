<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Equipment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/** @implements RepositoryInterface<Equipment> */
class EquipmentRepository extends AbstractRepository implements RepositoryInterface
{
    public function setModel(): string
    {
        return Equipment::class;
    }

    /** @return LengthAwarePaginator<int, Equipment> */
    public function searchPaginated(?string $term, int $limit = 15): LengthAwarePaginator
    {
        if (!$term) {
            return $this->query()->paginate($limit);
        }

        return $this->query()
            ->where(function ($query) use ($term) {
                $query->where('Equipment', 'like', "%{$term}%")
                    ->orWhere('Material', 'like', "%{$term}%")
                    ->orWhere('Description', 'like', "%{$term}%")
                    ->orWhere('Room', 'like', "%{$term}%");
            })
            ->paginate($limit);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function replaceAll(array $rows): void
    {
        DB::transaction(function () use ($rows) {
            Equipment::query()->delete();

            foreach (array_chunk($rows, 500) as $chunk) {
                Equipment::query()->insert($chunk);
            }
        });
    }
}
