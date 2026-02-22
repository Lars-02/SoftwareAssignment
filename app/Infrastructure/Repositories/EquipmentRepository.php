<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Models\Equipment;
use App\Domain\Repositories\EquipmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EquipmentRepository implements EquipmentRepositoryInterface
{
    /** @param Equipment[] $equipments */
    public function saveBatch(array $equipments): void
    {
        if ($equipments === []) {
            return;
        }

        $rows = [];

        foreach ($equipments as $equipment) {
            $rows[] = $equipment->getAttributes();
        }

        foreach (array_chunk($rows, 2000) as $chunk) {
            Equipment::query()->insert($chunk);
        }
    }

    public function getAll(string $search, int $paginate): LengthAwarePaginator
    {
        $query = Equipment::query();

        if ($search !== '') {
            $escaped = addcslashes($search, '\\%_');
            $like = "%{$escaped}%";

            $query->where(function ($builder) use ($like): void {
                $builder
                    ->where('Equipment', 'like', $like)
                    ->orWhere('Material', 'like', $like)
                    ->orWhere('Description', 'like', $like)
                    ->orWhere('Room', 'like', $like);
            });
        }

        return $query
            ->orderBy('Equipment')
            ->paginate($paginate)
            ->withQueryString();
    }
}
