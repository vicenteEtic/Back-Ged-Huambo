<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\UserController;

Route::get('/me', [UserController::class, 'me'])
    ->name('user.me');

Route::put('/changePassword/{id}', [UserController::class, 'changePassword'])
    ->name('user.changePassword');
Route::get('', [UserController::class, 'index'])
    ->name('user.index')
    ->middleware(['can:usuario-show']);

Route::post('', [UserController::class, 'store'])
    ->name('user.store')
    ->middleware(['can:usuario-create']);

Route::get('{id}', [UserController::class, 'show'])
    ->name('user.show')
    ->middleware(['can:usuario-show']);

Route::put('{id}', [UserController::class, 'update'])
    ->name('user.update')
    ->middleware(['can:usuario-edit']);

Route::post('/logout', [UserController::class, 'logout']);

