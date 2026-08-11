<?php

use App\Http\Controllers\RH\OverdueValue\OverdueValueController;
use Illuminate\Support\Facades\Route;

Route::get('/', [OverdueValueController::class, 'index'])->name('overdue_value.index')->middleware(['can:rh-valores-em-atraso-show']);
Route::post('/', [OverdueValueController::class, 'store'])->name('overdue_value.store')->middleware(['can:rh-valores-em-atraso-create']);
Route::get('summary', [OverdueValueController::class, 'summary'])->name('overdue_value.summary')->middleware(['can:rh-valores-em-atraso-show']);
Route::get('{id}', [OverdueValueController::class, 'show'])->name('overdue_value.show')->middleware(['can:rh-valores-em-atraso-show']);
Route::put('{id}', [OverdueValueController::class, 'update'])->name('overdue_value.update')->middleware(['can:rh-valores-em-atraso-edit']);
Route::delete('{id}', [OverdueValueController::class, 'destroy'])->name('overdue_value.destroy')->middleware(['can:rh-valores-em-atraso-delete']);
