<?php

use App\Http\Controllers\RH\Position\PositionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PositionController::class, 'index'])->name('position.index')->middleware(['can:rh-cargos-show']);
Route::post('/', [PositionController::class, 'store'])->name('position.store')->middleware(['can:rh-cargos-create']);
Route::get('{id}', [PositionController::class, 'show'])->name('position.show')->middleware(['can:rh-cargos-show']);
Route::put('{id}', [PositionController::class, 'update'])->name('position.update')->middleware(['can:rh-cargos-edit']);
Route::delete('{id}', [PositionController::class, 'destroy'])->name('position.destroy')->middleware(['can:rh-cargos-delete']);
