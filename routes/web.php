<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EquipmentController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/equipments', [EquipmentController::class, 'index'])->name('equipments.index');
