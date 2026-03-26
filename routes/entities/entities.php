<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Entities\EntitiesController;

Route::get('getProfileSegmentation', [EntitiesController::class, 'getProfileSegmentation'])
    ->name('entities.profileSegmentation')
    ->middleware('can:entidades-show');
Route::get('', [EntitiesController::class, 'index'])
    ->name('entities.index')
    ->middleware('can:entidades-show');

Route::post('import', [EntitiesController::class, 'storeImportData'])
    ->name('entities.import');

Route::post('', [EntitiesController::class, 'store'])
    ->name('entities.store')
    ->middleware('can:entidades-create');

Route::get('{entity}', [EntitiesController::class, 'show'])
    ->name('entities.show')
    ->middleware('can:entidades-show');

Route::put('{entity}', [EntitiesController::class, 'update'])
    ->name('entities.update')
    ->middleware('can:entidades-edit');

Route::delete('{entity}', [EntitiesController::class, 'destroy'])
    ->name('entities.destroy')
    ->middleware('can:entidades-delete');

