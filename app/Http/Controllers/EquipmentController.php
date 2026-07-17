<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Http\Requests\SearchEquipmentRequest;

class EquipmentController extends Controller
{
    public function index(SearchEquipmentRequest $request)
    {
        $search = $request->validated('search');

        $equipments = Equipment::query()
            ->when($search, function ($query, $value) {
                $query->where(function ($query) use ($value) {
                    $query->where('Equipment', 'like', "%{$value}%")
                        ->orWhere('Material', 'like', "%{$value}%")
                        ->orWhere('Description', 'like', "%{$value}%")
                        ->orWhere('Room', 'like', "%{$value}%");
                });
            })
            ->orderBy('Equipment')
            ->paginate(25)
            ->withQueryString();

        if ($request->ajax()) {
            return view('equipments.partials.table', compact('equipments'));
        }

        return view('equipments.index', compact('equipments'));
    }
}
