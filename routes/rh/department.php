<?php

use App\Http\Controllers\RH\Department\DepartmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DepartmentController::class, 'index'])->name('department.index')->middleware(['can:rh-departamentos-show']);
Route::post('/', [DepartmentController::class, 'store'])->name('department.store')->middleware(['can:rh-departamentos-create']);
Route::get('{id}', [DepartmentController::class, 'show'])->name('department.show')->middleware(['can:rh-departamentos-show']);
Route::put('{id}', [DepartmentController::class, 'update'])->name('department.update')->middleware(['can:rh-departamentos-edit']);
Route::delete('{id}', [DepartmentController::class, 'destroy'])->name('department.destroy')->middleware(['can:rh-departamentos-delete']);
