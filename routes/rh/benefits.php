<?php

use App\Http\Controllers\RH\Benefit\BenefitTypeController;
use App\Http\Controllers\RH\Benefit\EmployeeBenefitController;
use Illuminate\Support\Facades\Route;

Route::prefix('types')->group(function () {
    Route::get('/', [BenefitTypeController::class, 'index'])->name('benefit_type.index');
    Route::post('/', [BenefitTypeController::class, 'store'])->name('benefit_type.store');
    Route::get('{id}', [BenefitTypeController::class, 'show'])->name('benefit_type.show');
    Route::put('{id}', [BenefitTypeController::class, 'update'])->name('benefit_type.update');
    Route::delete('{id}', [BenefitTypeController::class, 'destroy'])->name('benefit_type.destroy');
});

Route::prefix('employee-benefits')->group(function () {
    Route::get('/', [EmployeeBenefitController::class, 'index'])->name('employee_benefit.index');
    Route::post('/', [EmployeeBenefitController::class, 'store'])->name('employee_benefit.store');
    Route::get('{id}', [EmployeeBenefitController::class, 'show'])->name('employee_benefit.show');
    Route::put('{id}', [EmployeeBenefitController::class, 'update'])->name('employee_benefit.update');
    Route::delete('{id}', [EmployeeBenefitController::class, 'destroy'])->name('employee_benefit.destroy');
});
