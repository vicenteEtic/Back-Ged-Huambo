<?php

use App\Http\Controllers\RH\Benefit\BenefitClaimController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BenefitClaimController::class, 'index']);
Route::post('/', [BenefitClaimController::class, 'store']);
Route::get('{id}', [BenefitClaimController::class, 'show']);
Route::put('{id}', [BenefitClaimController::class, 'update']);
Route::delete('{id}', [BenefitClaimController::class, 'destroy']);
