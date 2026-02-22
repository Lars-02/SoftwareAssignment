<?php

namespace App\Domain\Services;

use App\Domain\Repositories\EquipmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EquipmentDataGatherer
{
    public function __construct(
        private readonly EquipmentRepositoryInterface $equipmentRepository,
    ) {
    }

    public function getAllWithPaginate(int $paginate): LengthAwarePaginator
    {
        return $this->equipmentRepository->getAll($paginate);
    }
}
