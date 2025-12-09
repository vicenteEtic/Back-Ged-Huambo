<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Entities\RiskAssessmentController;
use App\Http\Controllers\Entities\RiskAssessmentControlController;
use App\Http\Controllers\Entities\RiskFormulaController;

/*
    |--------------------------------------------------------------------------
    | Risk Assessment Control
    |--------------------------------------------------------------------------
    */
    Route::prefix('risk-assessment-control')->group(function () {
        Route::get('', [RiskAssessmentControlController::class, 'index'])
            ->name('risk_assessment_control.index');

        Route::get('{id}', [RiskAssessmentControlController::class, 'show'])
            ->name('risk_assessment_control.show');
    });
    Route::get('total-risk-level-by-category', [RiskAssessmentController::class, 'getTotalRiskLevelByCategory'])
        ->name('risk_assessment.total_risk_level_by_category');

    Route::get('total-risk-level-by-profession', [RiskAssessmentController::class, 'getTotalRiskLevelByProfession'])
        ->name('risk_assessment.total_risk_level_by_profession');

    Route::get('total-risk-level-by-channel', [RiskAssessmentController::class, 'getTotalRiskLevelByChannel'])
        ->name('risk_assessment.total_risk_level_by_channel');

    Route::get('total-risk-level-by-pep', [RiskAssessmentController::class, 'getTotalRiskLevelByPep'])
        ->name('risk_assessment.total_risk_level_by_pep');

    Route::get('total-risk-level-by-country-residence', [RiskAssessmentController::class, 'getTotalRiskLevelByCountryResidence'])
        ->name('risk_assessment.total_risk_level_by_country_residence');

    Route::get('total-risk-level-by-nationality', [RiskAssessmentController::class, 'getTotalRiskLevelByNationality'])
        ->name('risk_assessment.total_risk_level_by_nationality');

    Route::get('total-risk-level-by-product-risk', [RiskAssessmentController::class, 'getTotalRiskLevelByProductRisk'])
        ->name('risk_assessment.total_risk_level_by_product_risk');

    Route::get('heat-map/{year?}', [RiskAssessmentController::class, 'getHeatMap'])
        ->name('risk_assessment.heat_map');
    

// Risk Formula
Route::get('riskFormula', [RiskFormulaController::class, 'index'])
        ->name('riskFormula.index')
        ->middleware('can:entidades-show');

    Route::post('riskFormula', [RiskFormulaController::class, 'store'])
        ->name('riskFormula.import');

    Route::put('riskFormula/{id}', [RiskFormulaController::class, 'update'])
        ->name('riskFormula.update')
        ->middleware('can:entidades-edit');

    Route::delete('riskFormula/{id}', [RiskFormulaController::class, 'destroy'])
        ->name('riskFormula.destroy')
        ->middleware('can:entidades-delete');

    // Rotas principais
    Route::get('', [RiskAssessmentController::class, 'index'])->name('risk_assessment.index');
    Route::post('', [RiskAssessmentController::class, 'store'])->name('risk_assessment.store');

    // `{id}` por último, para não conflitar com estatísticas
    Route::get('{id}', [RiskAssessmentController::class, 'show'])->name('risk_assessment.show');
    Route::put('{id}', [RiskAssessmentController::class, 'update'])->name('risk_assessment.update');
    Route::delete('{id}', [RiskAssessmentController::class, 'destroy'])->name('risk_assessment.destroy');



