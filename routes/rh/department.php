<?php

use App\Http\Controllers\RH\Department\DepartmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DepartmentController::class, 'index'])->name('department.index');
Route::post('/', [DepartmentController::class, 'store'])->name('department.store');
Route::get('{id}', [DepartmentController::class, 'show'])->name('department.show');
Route::put('{id}', [DepartmentController::class, 'update'])->name('department.update');
Route::delete('{id}', [DepartmentController::class, 'destroy'])->name('department.destroy');
