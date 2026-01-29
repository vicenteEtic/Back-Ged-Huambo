<?php

use App\Http\Controllers\Permission\RoleController;
use Illuminate\Support\Facades\Route;

Route::get('', [RoleController::class, 'index'])
    ->name('role.index')
    ->middleware(['can:regra-show']);

Route::post('', [RoleController::class, 'store'])
    ->name('role.store')
    ->middleware(['can:regra-create']);

Route::put('{role}', [RoleController::class, 'update'])
    ->name('role.update')
    ->middleware(['can:regra-edit']);

Route::delete('{role}', [RoleController::class, 'destroy'])
    ->name('role.destroy')
    ->middleware(['can:regra-delete']);

Route::get('{role}', [RoleController::class, 'show'])
    ->name('role.show')
    ->middleware(['can:regra-show']);
