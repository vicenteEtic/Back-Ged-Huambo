<?php

use App\Http\Controllers\RH\Reports\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('overview', [DashboardController::class, 'overview'])->middleware(['can:rh-relatorios-show']);
Route::get('monthly-birthdays', [DashboardController::class, 'monthlyBirthdays'])->middleware(['can:rh-relatorios-show']);
Route::get('leave-summary', [DashboardController::class, 'leaveSummary'])->middleware(['can:rh-relatorios-show']);
Route::get('attendance-summary', [DashboardController::class, 'attendanceSummary'])->middleware(['can:rh-relatorios-show']);
Route::get('document-expiry-alert', [DashboardController::class, 'documentExpiryAlert'])->middleware(['can:rh-relatorios-show']);
Route::get('turnover', [DashboardController::class, 'turnover'])->middleware(['can:rh-relatorios-show']);
Route::get('salary-evolution', [DashboardController::class, 'salaryEvolution'])->middleware(['can:rh-relatorios-show']);
