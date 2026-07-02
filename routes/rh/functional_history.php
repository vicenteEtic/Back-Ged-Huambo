<?php

use App\Http\Controllers\RH\FunctionalHistory\FunctionalHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FunctionalHistoryController::class, 'index'])->name('functional_history.index');
Route::post('/', [FunctionalHistoryController::class, 'store'])->name('functional_history.store');
Route::get('{id}', [FunctionalHistoryController::class, 'show'])->name('functional_history.show');
Route::put('{id}', [FunctionalHistoryController::class, 'update'])->name('functional_history.update');
Route::delete('{id}', [FunctionalHistoryController::class, 'destroy'])->name('functional_history.destroy');
