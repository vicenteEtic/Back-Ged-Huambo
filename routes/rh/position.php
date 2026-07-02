<?php

use App\Http\Controllers\RH\Position\PositionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PositionController::class, 'index'])->name('position.index');
Route::post('/', [PositionController::class, 'store'])->name('position.store');
Route::get('{id}', [PositionController::class, 'show'])->name('position.show');
Route::put('{id}', [PositionController::class, 'update'])->name('position.update');
Route::delete('{id}', [PositionController::class, 'destroy'])->name('position.destroy');
