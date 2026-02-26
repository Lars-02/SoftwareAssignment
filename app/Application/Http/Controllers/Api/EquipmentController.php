<?php

namespace App\Application\Http\Controllers\Api;

use App\Application\Http\Requests\EquipmentSearchRequest;
use App\Domain\Services\EquipmentDataGatherer;
use Illuminate\Http\JsonResponse;

class EquipmentController
{
    private const PER_PAGE = 20;

    public function __construct(
        private readonly EquipmentDataGatherer $equipmentDataGatherer,
    ) {
    }

    public function index(EquipmentSearchRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $search = trim((string) ($validated['search'] ?? ''));
        $paginator = $this->equipmentDataGatherer->getAllWithPaginate($search, self::PER_PAGE);

        return response()->json($paginator);
    }
}
