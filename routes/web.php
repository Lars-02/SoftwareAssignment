<?php

use App\Http\Controllers\Equipments\Index;
use Illuminate\Support\Facades\Route;

Route::get('/', Index::class)->name('equipments.index');
