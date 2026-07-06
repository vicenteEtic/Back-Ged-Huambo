<?php

use App\Http\Controllers\RH\Payroll\PayrollPeriodController;
use App\Http\Controllers\RH\Payroll\PayrollItemController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PayrollPeriodController::class, 'index'])->name('payroll.index')->middleware(['can:rh-processamento-show']);
Route::post('/', [PayrollPeriodController::class, 'store'])->name('payroll.store')->middleware(['can:rh-processamento-create']);

Route::prefix('periods')->group(function () {
    Route::get('/', [PayrollPeriodController::class, 'index'])->name('payroll_period.index')->middleware(['can:rh-processamento-show']);
    Route::post('/', [PayrollPeriodController::class, 'store'])->name('payroll_period.store')->middleware(['can:rh-processamento-create']);
    Route::get('{id}', [PayrollPeriodController::class, 'show'])->name('payroll_period.show')->middleware(['can:rh-processamento-show']);
    Route::put('{id}', [PayrollPeriodController::class, 'update'])->name('payroll_period.update')->middleware(['can:rh-processamento-edit']);
    Route::delete('{id}', [PayrollPeriodController::class, 'destroy'])->name('payroll_period.destroy')->middleware(['can:rh-processamento-delete']);
});

Route::prefix('items')->group(function () {
    Route::get('/', [PayrollItemController::class, 'index'])->name('payroll_item.index')->middleware(['can:rh-processamento-show']);
    Route::post('/', [PayrollItemController::class, 'store'])->name('payroll_item.store')->middleware(['can:rh-processamento-create']);
    Route::get('{id}', [PayrollItemController::class, 'show'])->name('payroll_item.show')->middleware(['can:rh-processamento-show']);
    Route::put('{id}', [PayrollItemController::class, 'update'])->name('payroll_item.update')->middleware(['can:rh-processamento-edit']);
    Route::delete('{id}', [PayrollItemController::class, 'destroy'])->name('payroll_item.destroy')->middleware(['can:rh-processamento-delete']);
});

Route::get('{id}', [PayrollPeriodController::class, 'show'])->name('payroll.show')->middleware(['can:rh-processamento-show']);
Route::put('{id}', [PayrollPeriodController::class, 'update'])->name('payroll.update')->middleware(['can:rh-processamento-edit']);
Route::delete('{id}', [PayrollPeriodController::class, 'destroy'])->name('payroll.destroy')->middleware(['can:rh-processamento-delete']);
