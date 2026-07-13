<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Equipment;

class EquipmentController extends Controller
{
    public function index()
    {
        $equipments = Equipment::orderBy('Equipment')->paginate(25);

        return view('equipments.index', compact('equipments'));
    }
}
