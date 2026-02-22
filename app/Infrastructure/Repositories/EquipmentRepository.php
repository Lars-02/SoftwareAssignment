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

    public function getAll(int $paginate): LengthAwarePaginator
    {
        return Equipment::query()
            ->orderBy('Equipment')
            ->paginate($paginate)
            ->withQueryString();
    }
}
