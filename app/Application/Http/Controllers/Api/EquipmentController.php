<?php

namespace App\Application\Http\Controllers\Api;

use App\Domain\Services\EquipmentDataGatherer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EquipmentController
{
    private const PER_PAGE = 20;

    public function __construct(
        private readonly EquipmentDataGatherer $equipmentDataGatherer,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->equipmentDataGatherer->getAllWithPaginate(self::PER_PAGE);

        return response()->json($paginator);
    }
}
