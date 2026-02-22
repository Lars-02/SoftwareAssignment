<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Models\Equipment;
use App\Domain\Repositories\EquipmentRepositoryInterface;

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
}
