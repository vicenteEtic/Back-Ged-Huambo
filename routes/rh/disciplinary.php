<?php

use App\Http\Controllers\RH\Disciplinary\DisciplinaryTypeController;
use App\Http\Controllers\RH\Disciplinary\DisciplinaryRecordController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DisciplinaryRecordController::class, 'index'])->name('disciplinary.index');
Route::post('/', [DisciplinaryRecordController::class, 'store'])->name('disciplinary.store');

Route::prefix('types')->group(function () {
    Route::get('/', [DisciplinaryTypeController::class, 'index'])->name('disciplinary_type.index');
    Route::post('/', [DisciplinaryTypeController::class, 'store'])->name('disciplinary_type.store');
    Route::get('{id}', [DisciplinaryTypeController::class, 'show'])->name('disciplinary_type.show');
    Route::put('{id}', [DisciplinaryTypeController::class, 'update'])->name('disciplinary_type.update');
    Route::delete('{id}', [DisciplinaryTypeController::class, 'destroy'])->name('disciplinary_type.destroy');
});

Route::prefix('records')->group(function () {
    Route::get('/', [DisciplinaryRecordController::class, 'index'])->name('disciplinary_record.index');
    Route::post('/', [DisciplinaryRecordController::class, 'store'])->name('disciplinary_record.store');
    Route::get('{id}', [DisciplinaryRecordController::class, 'show'])->name('disciplinary_record.show');
    Route::put('{id}', [DisciplinaryRecordController::class, 'update'])->name('disciplinary_record.update');
    Route::delete('{id}', [DisciplinaryRecordController::class, 'destroy'])->name('disciplinary_record.destroy');
});

Route::get('{id}', [DisciplinaryRecordController::class, 'show'])->name('disciplinary.show');
Route::put('{id}', [DisciplinaryRecordController::class, 'update'])->name('disciplinary.update');
Route::delete('{id}', [DisciplinaryRecordController::class, 'destroy'])->name('disciplinary.destroy');
