<?php

use App\Http\Controllers\RH\Career\RetirementController;
use Illuminate\Support\Facades\Route;

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
