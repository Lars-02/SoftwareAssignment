<?php

namespace App\Domain\Repositories;

use App\Domain\Models\Equipment;

interface EquipmentRepositoryInterface
{
    /** @param Equipment[] $equipments */
    public function saveBatch(array $equipments): void;
}
