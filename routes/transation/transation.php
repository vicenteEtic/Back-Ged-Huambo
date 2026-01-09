<?php



use App\Http\Controllers\Transation\PoliciesController;
use App\Http\Controllers\Transation\TransactionController;

use Illuminate\Support\Facades\Route;


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
