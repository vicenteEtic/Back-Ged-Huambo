<?php

use App\Http\Controllers\RH\Career\CareerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CareerController::class, 'index']);
Route::get('{employee_id}', [CareerController::class, 'show']);
