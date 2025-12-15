<?php

use App\Http\Controllers\Transation\TransationController;
use Illuminate\Support\Facades\Route;


Route::get('', [TransationController::class, 'index'])
    ->name('transation.index');

Route::post('', [TransationController::class, 'store'])
    ->name('transation.store');

Route::get('{transation}', [TransationController::class, 'show'])
    ->name('transation.show');

Route::put('{transation}', [TransationController::class, 'update'])
    ->name('transation.update');

Route::delete('{transation}', [TransationController::class, 'destroy'])
    ->name('transation.destroy');
