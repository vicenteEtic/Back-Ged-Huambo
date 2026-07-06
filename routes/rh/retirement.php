<?php

use App\Http\Controllers\RH\Career\RetirementController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RetirementController::class, 'index'])->name('retirement.index')->middleware(['can:rh-reforma-show']);
Route::post('/', [RetirementController::class, 'store'])->name('retirement.store')->middleware(['can:rh-reforma-create']);

Route::prefix('eligibility')->group(function () {
    Route::get('{employee_id}', [RetirementController::class, 'eligibility'])->middleware(['can:rh-reforma-show']);
});

Route::prefix('processes')->group(function () {
    Route::get('/', [RetirementController::class, 'index'])->middleware(['can:rh-reforma-show']);
    Route::post('/', [RetirementController::class, 'store'])->middleware(['can:rh-reforma-create']);
    Route::get('{id}', [RetirementController::class, 'show'])->middleware(['can:rh-reforma-show']);
    Route::put('{id}', [RetirementController::class, 'update'])->middleware(['can:rh-reforma-edit']);
    Route::delete('{id}', [RetirementController::class, 'destroy'])->middleware(['can:rh-reforma-delete']);
    Route::get('by-employee/{employee_id}', [RetirementController::class, 'history'])->middleware(['can:rh-reforma-show']);
});

Route::get('{id}', [RetirementController::class, 'show'])->name('retirement.show')->middleware(['can:rh-reforma-show']);
Route::put('{id}', [RetirementController::class, 'update'])->name('retirement.update')->middleware(['can:rh-reforma-edit']);
Route::delete('{id}', [RetirementController::class, 'destroy'])->name('retirement.destroy')->middleware(['can:rh-reforma-delete']);
