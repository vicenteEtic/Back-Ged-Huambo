<?php

use App\Http\Controllers\KYT\kytrulesController;
use App\Http\Controllers\Transation\PoliciesController;
use App\Http\Controllers\Transation\TransactionController;
use App\Http\Controllers\Transation\transaionControlController;
use Illuminate\Support\Facades\Route;

Route::resource('roles', kytrulesController::class);

    Route::get('/control', [transaionControlController::class, 'index'])
    ->name('control.index');

Route::post('/control', [transaionControlController::class, 'store'])
    ->name('control.store');

Route::get('/control/{control}', [transaionControlController::class, 'show'])
    ->name('control.show');

Route::put('/control/{control}', [TransactionController::class, 'update'])
    ->name('control.update');

Route::delete('/control/{control}', [transaionControlController::class, 'destroy'])
    ->name('control.destroy');


Route::get('', [PoliciesController::class, 'index'])
    ->name('transation.index');

Route::post('', [TransactionController::class, 'store'])
    ->name('transation.store');

Route::get('{transation}', [TransactionController::class, 'show'])
    ->name('transation.show');

Route::put('{transation}', [TransactionController::class, 'update'])
    ->name('transation.update');

Route::delete('{transation}', [TransactionController::class, 'destroy'])
    ->name('transation.destroy');

