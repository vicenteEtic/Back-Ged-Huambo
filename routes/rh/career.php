<?php

use App\Http\Controllers\RH\Career\CareerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CareerController::class, 'index'])->middleware(['can:rh-carreira-show']);
Route::get('{employee_id}', [CareerController::class, 'show'])->middleware(['can:rh-carreira-show']);
