<?php

namespace App\Application\Http\Controllers;

use Illuminate\View\View;

class EquipmentController
{
    public function index(): View
    {
        return view('index');
    }
}
