<?php

use App\Http\Controllers\RH\FunctionalHistory\FunctionalHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FunctionalHistoryController::class, 'index'])->name('functional_history.index')->middleware(['can:rh-historico-funcional-show']);
Route::post('/', [FunctionalHistoryController::class, 'store'])->name('functional_history.store')->middleware(['can:rh-historico-funcional-create']);
Route::get('{id}', [FunctionalHistoryController::class, 'show'])->name('functional_history.show')->middleware(['can:rh-historico-funcional-show']);
Route::put('{id}', [FunctionalHistoryController::class, 'update'])->name('functional_history.update')->middleware(['can:rh-historico-funcional-edit']);
Route::delete('{id}', [FunctionalHistoryController::class, 'destroy'])->name('functional_history.destroy')->middleware(['can:rh-historico-funcional-delete']);
