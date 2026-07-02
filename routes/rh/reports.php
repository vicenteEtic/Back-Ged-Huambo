<?php

use App\Http\Controllers\RH\Reports\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('overview', [DashboardController::class, 'overview']);
Route::get('monthly-birthdays', [DashboardController::class, 'monthlyBirthdays']);
Route::get('leave-summary', [DashboardController::class, 'leaveSummary']);
Route::get('attendance-summary', [DashboardController::class, 'attendanceSummary']);
Route::get('document-expiry-alert', [DashboardController::class, 'documentExpiryAlert']);
Route::get('turnover', [DashboardController::class, 'turnover']);
Route::get('salary-evolution', [DashboardController::class, 'salaryEvolution']);
