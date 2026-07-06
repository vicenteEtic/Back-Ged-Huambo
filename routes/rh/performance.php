<?php

use App\Http\Controllers\RH\Performance\EvaluationCriterionController;
use App\Http\Controllers\RH\Performance\EvaluationScoreController;
use App\Http\Controllers\RH\Performance\PerformanceCycleController;
use App\Http\Controllers\RH\Performance\PerformanceGoalController;
use App\Http\Controllers\RH\Performance\PerformanceEvaluationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PerformanceCycleController::class, 'index'])->name('performance.index')->middleware(['can:rh-desempenho-show']);
Route::post('/', [PerformanceCycleController::class, 'store'])->name('performance.store')->middleware(['can:rh-desempenho-create']);

Route::prefix('cycles')->group(function () {
    Route::get('/', [PerformanceCycleController::class, 'index'])->name('performance_cycle.index')->middleware(['can:rh-desempenho-show']);
    Route::post('/', [PerformanceCycleController::class, 'store'])->name('performance_cycle.store')->middleware(['can:rh-desempenho-create']);
    Route::get('{id}', [PerformanceCycleController::class, 'show'])->name('performance_cycle.show')->middleware(['can:rh-desempenho-show']);
    Route::put('{id}', [PerformanceCycleController::class, 'update'])->name('performance_cycle.update')->middleware(['can:rh-desempenho-edit']);
    Route::delete('{id}', [PerformanceCycleController::class, 'destroy'])->name('performance_cycle.destroy')->middleware(['can:rh-desempenho-delete']);
});

Route::prefix('goals')->group(function () {
    Route::get('/', [PerformanceGoalController::class, 'index'])->name('performance_goal.index')->middleware(['can:rh-desempenho-show']);
    Route::post('/', [PerformanceGoalController::class, 'store'])->name('performance_goal.store')->middleware(['can:rh-desempenho-create']);
    Route::get('{id}', [PerformanceGoalController::class, 'show'])->name('performance_goal.show')->middleware(['can:rh-desempenho-show']);
    Route::put('{id}', [PerformanceGoalController::class, 'update'])->name('performance_goal.update')->middleware(['can:rh-desempenho-edit']);
    Route::delete('{id}', [PerformanceGoalController::class, 'destroy'])->name('performance_goal.destroy')->middleware(['can:rh-desempenho-delete']);
});

Route::prefix('evaluations')->group(function () {
    Route::get('/', [PerformanceEvaluationController::class, 'index'])->name('performance_evaluation.index')->middleware(['can:rh-desempenho-show']);
    Route::post('/', [PerformanceEvaluationController::class, 'store'])->name('performance_evaluation.store')->middleware(['can:rh-desempenho-create']);
    Route::get('{id}', [PerformanceEvaluationController::class, 'show'])->name('performance_evaluation.show')->middleware(['can:rh-desempenho-show']);
    Route::put('{id}', [PerformanceEvaluationController::class, 'update'])->name('performance_evaluation.update')->middleware(['can:rh-desempenho-edit']);
    Route::delete('{id}', [PerformanceEvaluationController::class, 'destroy'])->name('performance_evaluation.destroy')->middleware(['can:rh-desempenho-delete']);
    Route::get('{id}/scores', [EvaluationScoreController::class, 'byEvaluation'])->name('evaluation_score.by_evaluation')->middleware(['can:rh-desempenho-show']);
    Route::post('{id}/calculate', [PerformanceEvaluationController::class, 'calculate'])->name('performance_evaluation.calculate')->middleware(['can:rh-desempenho-edit']);
});

Route::prefix('criteria')->group(function () {
    Route::get('/', [EvaluationCriterionController::class, 'index'])->name('evaluation_criterion.index')->middleware(['can:rh-desempenho-show']);
    Route::post('/', [EvaluationCriterionController::class, 'store'])->name('evaluation_criterion.store')->middleware(['can:rh-desempenho-create']);
    Route::get('{id}', [EvaluationCriterionController::class, 'show'])->name('evaluation_criterion.show')->middleware(['can:rh-desempenho-show']);
    Route::put('{id}', [EvaluationCriterionController::class, 'update'])->name('evaluation_criterion.update')->middleware(['can:rh-desempenho-edit']);
    Route::delete('{id}', [EvaluationCriterionController::class, 'destroy'])->name('evaluation_criterion.destroy')->middleware(['can:rh-desempenho-delete']);
});

Route::prefix('scores')->group(function () {
    Route::get('/', [EvaluationScoreController::class, 'index'])->name('evaluation_score.index')->middleware(['can:rh-desempenho-show']);
    Route::post('/', [EvaluationScoreController::class, 'store'])->name('evaluation_score.store')->middleware(['can:rh-desempenho-create']);
    Route::get('{id}', [EvaluationScoreController::class, 'show'])->name('evaluation_score.show')->middleware(['can:rh-desempenho-show']);
    Route::put('{id}', [EvaluationScoreController::class, 'update'])->name('evaluation_score.update')->middleware(['can:rh-desempenho-edit']);
    Route::delete('{id}', [EvaluationScoreController::class, 'destroy'])->name('evaluation_score.destroy')->middleware(['can:rh-desempenho-delete']);
});

Route::get('{id}', [PerformanceCycleController::class, 'show'])->name('performance.show')->middleware(['can:rh-desempenho-show']);
Route::put('{id}', [PerformanceCycleController::class, 'update'])->name('performance.update')->middleware(['can:rh-desempenho-edit']);
Route::delete('{id}', [PerformanceCycleController::class, 'destroy'])->name('performance.destroy')->middleware(['can:rh-desempenho-delete']);
