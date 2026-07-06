<?php

use App\Http\Controllers\RH\Performance\EvaluationCriterionController;
use App\Http\Controllers\RH\Performance\EvaluationScoreController;
use App\Http\Controllers\RH\Performance\PerformanceCycleController;
use App\Http\Controllers\RH\Performance\PerformanceGoalController;
use App\Http\Controllers\RH\Performance\PerformanceEvaluationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PerformanceCycleController::class, 'index'])->name('performance.index');
Route::post('/', [PerformanceCycleController::class, 'store'])->name('performance.store');

Route::prefix('cycles')->group(function () {
    Route::get('/', [PerformanceCycleController::class, 'index'])->name('performance_cycle.index');
    Route::post('/', [PerformanceCycleController::class, 'store'])->name('performance_cycle.store');
    Route::get('{id}', [PerformanceCycleController::class, 'show'])->name('performance_cycle.show');
    Route::put('{id}', [PerformanceCycleController::class, 'update'])->name('performance_cycle.update');
    Route::delete('{id}', [PerformanceCycleController::class, 'destroy'])->name('performance_cycle.destroy');
});

Route::prefix('goals')->group(function () {
    Route::get('/', [PerformanceGoalController::class, 'index'])->name('performance_goal.index');
    Route::post('/', [PerformanceGoalController::class, 'store'])->name('performance_goal.store');
    Route::get('{id}', [PerformanceGoalController::class, 'show'])->name('performance_goal.show');
    Route::put('{id}', [PerformanceGoalController::class, 'update'])->name('performance_goal.update');
    Route::delete('{id}', [PerformanceGoalController::class, 'destroy'])->name('performance_goal.destroy');
});

Route::prefix('evaluations')->group(function () {
    Route::get('/', [PerformanceEvaluationController::class, 'index'])->name('performance_evaluation.index');
    Route::post('/', [PerformanceEvaluationController::class, 'store'])->name('performance_evaluation.store');
    Route::get('{id}', [PerformanceEvaluationController::class, 'show'])->name('performance_evaluation.show');
    Route::put('{id}', [PerformanceEvaluationController::class, 'update'])->name('performance_evaluation.update');
    Route::delete('{id}', [PerformanceEvaluationController::class, 'destroy'])->name('performance_evaluation.destroy');
    Route::get('{id}/scores', [EvaluationScoreController::class, 'byEvaluation'])->name('evaluation_score.by_evaluation');
    Route::post('{id}/calculate', [PerformanceEvaluationController::class, 'calculate'])->name('performance_evaluation.calculate');
});

Route::prefix('criteria')->group(function () {
    Route::get('/', [EvaluationCriterionController::class, 'index'])->name('evaluation_criterion.index');
    Route::post('/', [EvaluationCriterionController::class, 'store'])->name('evaluation_criterion.store');
    Route::get('{id}', [EvaluationCriterionController::class, 'show'])->name('evaluation_criterion.show');
    Route::put('{id}', [EvaluationCriterionController::class, 'update'])->name('evaluation_criterion.update');
    Route::delete('{id}', [EvaluationCriterionController::class, 'destroy'])->name('evaluation_criterion.destroy');
});

Route::prefix('scores')->group(function () {
    Route::get('/', [EvaluationScoreController::class, 'index'])->name('evaluation_score.index');
    Route::post('/', [EvaluationScoreController::class, 'store'])->name('evaluation_score.store');
    Route::get('{id}', [EvaluationScoreController::class, 'show'])->name('evaluation_score.show');
    Route::put('{id}', [EvaluationScoreController::class, 'update'])->name('evaluation_score.update');
    Route::delete('{id}', [EvaluationScoreController::class, 'destroy'])->name('evaluation_score.destroy');
});

Route::get('{id}', [PerformanceCycleController::class, 'show'])->name('performance.show');
Route::put('{id}', [PerformanceCycleController::class, 'update'])->name('performance.update');
Route::delete('{id}', [PerformanceCycleController::class, 'destroy'])->name('performance.destroy');
