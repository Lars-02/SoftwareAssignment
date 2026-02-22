<?php

namespace App\Domain\Repositories;

use App\Domain\Models\Equipment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EquipmentRepositoryInterface
{
    /** @param Equipment[] $equipments */
    public function saveBatch(array $equipments): void;

    public function getAll(string $search, int $paginate): LengthAwarePaginator;
}
