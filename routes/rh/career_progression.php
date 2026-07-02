<?php

use App\Http\Controllers\RH\Career\ProgressionRuleController;
use App\Http\Controllers\RH\Career\ProgressionRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('rules')->group(function () {
    Route::get('/', [ProgressionRuleController::class, 'index'])->name('progression_rule.index');
    Route::post('/', [ProgressionRuleController::class, 'store'])->name('progression_rule.store');
    Route::get('{id}', [ProgressionRuleController::class, 'show'])->name('progression_rule.show');
    Route::put('{id}', [ProgressionRuleController::class, 'update'])->name('progression_rule.update');
    Route::delete('{id}', [ProgressionRuleController::class, 'destroy'])->name('progression_rule.destroy');
    Route::get('{id}/check-eligibility/{employee_id}', [ProgressionRuleController::class, 'checkEligibility'])->name('progression_rule.check');
});

Route::prefix('requests')->group(function () {
    Route::get('/', [ProgressionRequestController::class, 'index'])->name('progression_request.index');
    Route::post('/', [ProgressionRequestController::class, 'store'])->name('progression_request.store');
    Route::get('{id}', [ProgressionRequestController::class, 'show'])->name('progression_request.show');
    Route::put('{id}', [ProgressionRequestController::class, 'update'])->name('progression_request.update');
    Route::delete('{id}', [ProgressionRequestController::class, 'destroy'])->name('progression_request.destroy');
    Route::post('{id}/approve', [ProgressionRequestController::class, 'approve'])->name('progression_request.approve');
    Route::post('{id}/reject', [ProgressionRequestController::class, 'reject'])->name('progression_request.reject');
    Route::post('{id}/execute', [ProgressionRequestController::class, 'execute'])->name('progression_request.execute');
});
