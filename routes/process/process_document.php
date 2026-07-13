<?php

use App\Http\Controllers\Process\ProcessDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProcessDocumentController::class, 'index'])->name('process-document.byProcess')->middleware(['can:rh-processos-show']);
Route::post('/', [ProcessDocumentController::class, 'store'])->name('process-document.store')->middleware(['can:rh-processos-create']);
Route::delete('{id}', [ProcessDocumentController::class, 'destroy'])->name('process-document.destroy')->middleware(['can:rh-processos-delete']);
