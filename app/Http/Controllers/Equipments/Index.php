<?php

declare(strict_types=1);

namespace App\Http\Controllers\Equipments;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchEquipmentsRequest;
use App\Repositories\EquipmentRepository;
use Illuminate\View\View;

class Index extends Controller
{
    public function __construct(
        private readonly EquipmentRepository $equipments,
    ) {
    }

    public function __invoke(SearchEquipmentsRequest $request): View
    {
        $search = $request->search();

        return view('equipments.index', [
            'equipments' => $this->equipments->searchPaginated($search, 15)->withQueryString(),
            'search' => $search,
        ]);
    }
}
