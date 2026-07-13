<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EquipmentController;

Route::redirect('/', '/equipments');

Route::get('/equipments', [EquipmentController::class, 'index'])->name('equipments.index');
