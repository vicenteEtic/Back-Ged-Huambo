<?php

use App\Http\Controllers\RH\Employee\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EmployeeController::class, 'index'])->name('employee.index');
Route::post('/', [EmployeeController::class, 'store'])->name('employee.store');
Route::get('{id}', [EmployeeController::class, 'show'])->name('employee.show');
Route::put('{id}', [EmployeeController::class, 'update'])->name('employee.update');
Route::delete('{id}', [EmployeeController::class, 'destroy'])->name('employee.destroy');
