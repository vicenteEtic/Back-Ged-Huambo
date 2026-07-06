<?php

use App\Http\Controllers\RH\Payroll\PayslipController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PayslipController::class, 'index'])->middleware(['can:rh-salarios-show']);
Route::get('{id}', [PayslipController::class, 'show'])->middleware(['can:rh-salarios-show']);
Route::get('by-employee/{employee_id}', [PayslipController::class, 'byEmployee'])->middleware(['can:rh-salarios-show']);
Route::post('generate/{period_id}', [PayslipController::class, 'generate'])->middleware(['can:rh-salarios-create']);
