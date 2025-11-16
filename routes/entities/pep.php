<?php

use App\Http\Controllers\Entities\PepController;
use Illuminate\Support\Facades\Route;

Route::get('', [PepController::class, 'index'])
    ->name('pep.index');

Route::post('', [PepController::class, 'store'])
    ->name('pep.store');

    Route::put('/{pep}', [PepController::class, 'update'])
    ->name('pep.update');

Route::get('external', [PepController::class, 'pepExternalGetAll'])
    ->name('pep.external');

    Route::get('sanction', [PepController::class, 'getDataSanctionExternal'])
    ->name('pep.sanction');
    