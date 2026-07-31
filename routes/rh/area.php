<?php

use App\Http\Controllers\RH\Area\AreaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AreaController::class, 'index'])->name('area.index')->middleware(['can:areas-show']);
Route::post('/', [AreaController::class, 'store'])->name('area.store')->middleware(['can:areas-create']);
Route::get('{id}', [AreaController::class, 'show'])->name('area.show')->middleware(['can:areas-show']);
Route::put('{id}', [AreaController::class, 'update'])->name('area.update')->middleware(['can:areas-edit']);
Route::delete('{id}', [AreaController::class, 'destroy'])->name('area.destroy')->middleware(['can:areas-delete']);
Route::get('by-department/{departmentId}', [AreaController::class, 'byDepartment'])->name('area.byDepartment')->middleware(['can:areas-show']);
