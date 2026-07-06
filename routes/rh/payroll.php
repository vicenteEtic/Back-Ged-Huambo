<?php

use App\Http\Controllers\RH\Payroll\PayrollPeriodController;
use App\Http\Controllers\RH\Payroll\PayrollItemController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PayrollPeriodController::class, 'index'])->name('payroll.index');
Route::post('/', [PayrollPeriodController::class, 'store'])->name('payroll.store');

Route::prefix('periods')->group(function () {
    Route::get('/', [PayrollPeriodController::class, 'index'])->name('payroll_period.index');
    Route::post('/', [PayrollPeriodController::class, 'store'])->name('payroll_period.store');
    Route::get('{id}', [PayrollPeriodController::class, 'show'])->name('payroll_period.show');
    Route::put('{id}', [PayrollPeriodController::class, 'update'])->name('payroll_period.update');
    Route::delete('{id}', [PayrollPeriodController::class, 'destroy'])->name('payroll_period.destroy');
});

Route::prefix('items')->group(function () {
    Route::get('/', [PayrollItemController::class, 'index'])->name('payroll_item.index');
    Route::post('/', [PayrollItemController::class, 'store'])->name('payroll_item.store');
    Route::get('{id}', [PayrollItemController::class, 'show'])->name('payroll_item.show');
    Route::put('{id}', [PayrollItemController::class, 'update'])->name('payroll_item.update');
    Route::delete('{id}', [PayrollItemController::class, 'destroy'])->name('payroll_item.destroy');
});

Route::get('{id}', [PayrollPeriodController::class, 'show'])->name('payroll.show');
Route::put('{id}', [PayrollPeriodController::class, 'update'])->name('payroll.update');
Route::delete('{id}', [PayrollPeriodController::class, 'destroy'])->name('payroll.destroy');
