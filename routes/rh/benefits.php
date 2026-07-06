<?php

use App\Http\Controllers\RH\Benefit\BenefitTypeController;
use App\Http\Controllers\RH\Benefit\EmployeeBenefitController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EmployeeBenefitController::class, 'index'])->name('benefit.index')->middleware(['can:rh-beneficios-show']);
Route::post('/', [EmployeeBenefitController::class, 'store'])->name('benefit.store')->middleware(['can:rh-beneficios-create']);

Route::prefix('types')->group(function () {
    Route::get('/', [BenefitTypeController::class, 'index'])->name('benefit_type.index')->middleware(['can:rh-beneficios-show']);
    Route::post('/', [BenefitTypeController::class, 'store'])->name('benefit_type.store')->middleware(['can:rh-beneficios-create']);
    Route::get('{id}', [BenefitTypeController::class, 'show'])->name('benefit_type.show')->middleware(['can:rh-beneficios-show']);
    Route::put('{id}', [BenefitTypeController::class, 'update'])->name('benefit_type.update')->middleware(['can:rh-beneficios-edit']);
    Route::delete('{id}', [BenefitTypeController::class, 'destroy'])->name('benefit_type.destroy')->middleware(['can:rh-beneficios-delete']);
});

Route::prefix('employee-benefits')->group(function () {
    Route::get('/', [EmployeeBenefitController::class, 'index'])->name('employee_benefit.index')->middleware(['can:rh-beneficios-show']);
    Route::post('/', [EmployeeBenefitController::class, 'store'])->name('employee_benefit.store')->middleware(['can:rh-beneficios-create']);
    Route::get('{id}', [EmployeeBenefitController::class, 'show'])->name('employee_benefit.show')->middleware(['can:rh-beneficios-show']);
    Route::put('{id}', [EmployeeBenefitController::class, 'update'])->name('employee_benefit.update')->middleware(['can:rh-beneficios-edit']);
    Route::delete('{id}', [EmployeeBenefitController::class, 'destroy'])->name('employee_benefit.destroy')->middleware(['can:rh-beneficios-delete']);
});

Route::get('{id}', [EmployeeBenefitController::class, 'show'])->name('benefit.show')->middleware(['can:rh-beneficios-show']);
Route::put('{id}', [EmployeeBenefitController::class, 'update'])->name('benefit.update')->middleware(['can:rh-beneficios-edit']);
Route::delete('{id}', [EmployeeBenefitController::class, 'destroy'])->name('benefit.destroy')->middleware(['can:rh-beneficios-delete']);
