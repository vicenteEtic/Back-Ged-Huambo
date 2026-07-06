<?php

use App\Http\Controllers\RH\Career\ProgressionRuleController;
use App\Http\Controllers\RH\Career\ProgressionRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProgressionRequestController::class, 'index'])->name('progression.index')->middleware(['can:rh-progressao-show']);
Route::post('/', [ProgressionRequestController::class, 'store'])->name('progression.store')->middleware(['can:rh-progressao-create']);

Route::prefix('rules')->group(function () {
    Route::get('/', [ProgressionRuleController::class, 'index'])->name('progression_rule.index')->middleware(['can:rh-progressao-show']);
    Route::post('/', [ProgressionRuleController::class, 'store'])->name('progression_rule.store')->middleware(['can:rh-progressao-create']);
    Route::get('{id}', [ProgressionRuleController::class, 'show'])->name('progression_rule.show')->middleware(['can:rh-progressao-show']);
    Route::put('{id}', [ProgressionRuleController::class, 'update'])->name('progression_rule.update')->middleware(['can:rh-progressao-edit']);
    Route::delete('{id}', [ProgressionRuleController::class, 'destroy'])->name('progression_rule.destroy')->middleware(['can:rh-progressao-delete']);
    Route::get('{id}/check-eligibility/{employee_id}', [ProgressionRuleController::class, 'checkEligibility'])->name('progression_rule.check')->middleware(['can:rh-progressao-show']);
});

Route::prefix('requests')->group(function () {
    Route::get('/', [ProgressionRequestController::class, 'index'])->name('progression_request.index')->middleware(['can:rh-progressao-show']);
    Route::post('/', [ProgressionRequestController::class, 'store'])->name('progression_request.store')->middleware(['can:rh-progressao-create']);
    Route::get('{id}', [ProgressionRequestController::class, 'show'])->name('progression_request.show')->middleware(['can:rh-progressao-show']);
    Route::put('{id}', [ProgressionRequestController::class, 'update'])->name('progression_request.update')->middleware(['can:rh-progressao-edit']);
    Route::delete('{id}', [ProgressionRequestController::class, 'destroy'])->name('progression_request.destroy')->middleware(['can:rh-progressao-delete']);
    Route::post('{id}/approve', [ProgressionRequestController::class, 'approve'])->name('progression_request.approve')->middleware(['can:rh-progressao-edit']);
    Route::post('{id}/reject', [ProgressionRequestController::class, 'reject'])->name('progression_request.reject')->middleware(['can:rh-progressao-edit']);
    Route::post('{id}/execute', [ProgressionRequestController::class, 'execute'])->name('progression_request.execute')->middleware(['can:rh-progressao-edit']);
});

Route::get('{id}', [ProgressionRequestController::class, 'show'])->name('progression.show')->middleware(['can:rh-progressao-show']);
Route::put('{id}', [ProgressionRequestController::class, 'update'])->name('progression.update')->middleware(['can:rh-progressao-edit']);
Route::delete('{id}', [ProgressionRequestController::class, 'destroy'])->name('progression.destroy')->middleware(['can:rh-progressao-delete']);
