<?php

use App\Http\Controllers\RH\Portal\EmployeePortalController;
use Illuminate\Support\Facades\Route;

Route::get('profile', [EmployeePortalController::class, 'profile']);
Route::get('leave-balance', [EmployeePortalController::class, 'leaveBalance']);
Route::get('salary-history', [EmployeePortalController::class, 'salaryHistory']);
Route::get('career', [EmployeePortalController::class, 'career']);
Route::get('benefits', [EmployeePortalController::class, 'benefits']);
Route::post('payslip/{id}/download', [EmployeePortalController::class, 'payslipDownload']);
