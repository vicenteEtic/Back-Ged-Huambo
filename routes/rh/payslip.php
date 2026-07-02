<?php

use App\Http\Controllers\RH\Payroll\PayslipController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PayslipController::class, 'index']);
Route::get('{id}', [PayslipController::class, 'show']);
Route::get('by-employee/{employee_id}', [PayslipController::class, 'byEmployee']);
Route::post('generate/{period_id}', [PayslipController::class, 'generate']);
