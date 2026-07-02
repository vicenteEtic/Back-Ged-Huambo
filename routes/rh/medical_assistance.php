<?php

use App\Http\Controllers\RH\Benefit\MedicalAssistanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MedicalAssistanceController::class, 'index']);
Route::post('/', [MedicalAssistanceController::class, 'store']);
Route::get('{id}', [MedicalAssistanceController::class, 'show']);
Route::put('{id}', [MedicalAssistanceController::class, 'update']);
Route::delete('{id}', [MedicalAssistanceController::class, 'destroy']);
