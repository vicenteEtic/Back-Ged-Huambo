<?php

use App\Http\Controllers\RH\Employee\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EmployeeController::class, 'index'])->name('employee.index')->middleware(['can:rh-funcionarios-show']);
Route::post('/', [EmployeeController::class, 'store'])->name('employee.store')->middleware(['can:rh-funcionarios-create']);
Route::get('{id}', [EmployeeController::class, 'show'])->name('employee.show')->middleware(['can:rh-funcionarios-show']);
Route::put('{id}', [EmployeeController::class, 'update'])->name('employee.update')->middleware(['can:rh-funcionarios-edit']);
Route::delete('{id}', [EmployeeController::class, 'destroy'])->name('employee.destroy')->middleware(['can:rh-funcionarios-delete']);
