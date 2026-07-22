<?php

use App\Http\Controllers\RH\Payroll\IrtBracketController;
use Illuminate\Support\Facades\Route;

Route::get('/', [IrtBracketController::class, 'index'])->name('irt_bracket.index')->middleware(['can:rh-processamento-show']);
Route::post('/', [IrtBracketController::class, 'store'])->name('irt_bracket.store')->middleware(['can:rh-processamento-create']);
Route::get('{id}', [IrtBracketController::class, 'show'])->name('irt_bracket.show')->middleware(['can:rh-processamento-show']);
Route::put('{id}', [IrtBracketController::class, 'update'])->name('irt_bracket.update')->middleware(['can:rh-processamento-edit']);
Route::delete('{id}', [IrtBracketController::class, 'destroy'])->name('irt_bracket.destroy')->middleware(['can:rh-processamento-delete']);
