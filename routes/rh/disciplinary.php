<?php

use App\Http\Controllers\RH\Disciplinary\DisciplinaryTypeController;
use App\Http\Controllers\RH\Disciplinary\DisciplinaryRecordController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DisciplinaryRecordController::class, 'index'])->name('disciplinary.index')->middleware(['can:rh-disciplina-show']);
Route::post('/', [DisciplinaryRecordController::class, 'store'])->name('disciplinary.store')->middleware(['can:rh-disciplina-create']);

Route::prefix('types')->group(function () {
    Route::get('/', [DisciplinaryTypeController::class, 'index'])->name('disciplinary_type.index')->middleware(['can:rh-disciplina-show']);
    Route::post('/', [DisciplinaryTypeController::class, 'store'])->name('disciplinary_type.store')->middleware(['can:rh-disciplina-create']);
    Route::get('{id}', [DisciplinaryTypeController::class, 'show'])->name('disciplinary_type.show')->middleware(['can:rh-disciplina-show']);
    Route::put('{id}', [DisciplinaryTypeController::class, 'update'])->name('disciplinary_type.update')->middleware(['can:rh-disciplina-edit']);
    Route::delete('{id}', [DisciplinaryTypeController::class, 'destroy'])->name('disciplinary_type.destroy')->middleware(['can:rh-disciplina-delete']);
});

Route::prefix('records')->group(function () {
    Route::get('/', [DisciplinaryRecordController::class, 'index'])->name('disciplinary_record.index')->middleware(['can:rh-disciplina-show']);
    Route::post('/', [DisciplinaryRecordController::class, 'store'])->name('disciplinary_record.store')->middleware(['can:rh-disciplina-create']);
    Route::get('{id}', [DisciplinaryRecordController::class, 'show'])->name('disciplinary_record.show')->middleware(['can:rh-disciplina-show']);
    Route::put('{id}', [DisciplinaryRecordController::class, 'update'])->name('disciplinary_record.update')->middleware(['can:rh-disciplina-edit']);
    Route::delete('{id}', [DisciplinaryRecordController::class, 'destroy'])->name('disciplinary_record.destroy')->middleware(['can:rh-disciplina-delete']);
});

Route::get('{id}', [DisciplinaryRecordController::class, 'show'])->name('disciplinary.show')->middleware(['can:rh-disciplina-show']);
Route::put('{id}', [DisciplinaryRecordController::class, 'update'])->name('disciplinary.update')->middleware(['can:rh-disciplina-edit']);
Route::delete('{id}', [DisciplinaryRecordController::class, 'destroy'])->name('disciplinary.destroy')->middleware(['can:rh-disciplina-delete']);
