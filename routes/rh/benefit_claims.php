<?php

use App\Http\Controllers\RH\Benefit\BenefitClaimController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BenefitClaimController::class, 'index'])->middleware(['can:rh-pedidos-beneficios-show']);
Route::post('/', [BenefitClaimController::class, 'store'])->middleware(['can:rh-pedidos-beneficios-create']);
Route::get('{id}', [BenefitClaimController::class, 'show'])->middleware(['can:rh-pedidos-beneficios-show']);
Route::put('{id}', [BenefitClaimController::class, 'update'])->middleware(['can:rh-pedidos-beneficios-edit']);
Route::delete('{id}', [BenefitClaimController::class, 'destroy'])->middleware(['can:rh-pedidos-beneficios-delete']);
