<?php

use App\Http\Controllers\RH\Benefit\MedicalAssistanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MedicalAssistanceController::class, 'index'])->middleware(['can:rh-assistencia-medica-show']);
Route::post('/', [MedicalAssistanceController::class, 'store'])->middleware(['can:rh-assistencia-medica-create']);
Route::get('{id}', [MedicalAssistanceController::class, 'show'])->middleware(['can:rh-assistencia-medica-show']);
Route::put('{id}', [MedicalAssistanceController::class, 'update'])->middleware(['can:rh-assistencia-medica-edit']);
Route::delete('{id}', [MedicalAssistanceController::class, 'destroy'])->middleware(['can:rh-assistencia-medica-delete']);
