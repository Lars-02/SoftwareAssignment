<?php

use App\Application\Http\Controllers\Api\EquipmentController as ApiEquipmentController;
use App\Application\Http\Controllers\EquipmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EquipmentController::class, 'index'])->name('equipments.index');
Route::get('/equipments', [ApiEquipmentController::class, 'index'])->name('equipments.data');
