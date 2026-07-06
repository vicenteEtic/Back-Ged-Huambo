<?php

use App\Http\Controllers\RH\Career\RetirementController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RetirementController::class, 'index'])->name('retirement.index');
Route::post('/', [RetirementController::class, 'store'])->name('retirement.store');

Route::prefix('eligibility')->group(function () {
    Route::get('{employee_id}', [RetirementController::class, 'eligibility']);
});

Route::prefix('processes')->group(function () {
    Route::get('/', [RetirementController::class, 'index']);
    Route::post('/', [RetirementController::class, 'store']);
    Route::get('{id}', [RetirementController::class, 'show']);
    Route::put('{id}', [RetirementController::class, 'update']);
    Route::delete('{id}', [RetirementController::class, 'destroy']);
    Route::get('by-employee/{employee_id}', [RetirementController::class, 'history']);
});

Route::get('{id}', [RetirementController::class, 'show'])->name('retirement.show');
Route::put('{id}', [RetirementController::class, 'update'])->name('retirement.update');
Route::delete('{id}', [RetirementController::class, 'destroy'])->name('retirement.destroy');
