<?php

use App\Http\Controllers\RH\Portal\EmployeePortalController;
use Illuminate\Support\Facades\Route;

Route::get('profile', [EmployeePortalController::class, 'profile'])->middleware(['can:rh-portal-show']);
Route::get('leave-balance', [EmployeePortalController::class, 'leaveBalance'])->middleware(['can:rh-portal-show']);
Route::get('salary-history', [EmployeePortalController::class, 'salaryHistory'])->middleware(['can:rh-portal-show']);
Route::get('career', [EmployeePortalController::class, 'career'])->middleware(['can:rh-portal-show']);
Route::get('benefits', [EmployeePortalController::class, 'benefits'])->middleware(['can:rh-portal-show']);
Route::post('payslip/{id}/download', [EmployeePortalController::class, 'payslipDownload'])->middleware(['can:rh-portal-show']);
