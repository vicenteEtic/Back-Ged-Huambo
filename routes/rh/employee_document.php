<?php

use App\Http\Controllers\RH\EmployeeDocument\EmployeeDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EmployeeDocumentController::class, 'index'])->name('employee_document.index');
Route::post('/', [EmployeeDocumentController::class, 'store'])->name('employee_document.store');
Route::get('{id}', [EmployeeDocumentController::class, 'show'])->name('employee_document.show');
Route::put('{id}', [EmployeeDocumentController::class, 'update'])->name('employee_document.update');
Route::delete('{id}', [EmployeeDocumentController::class, 'destroy'])->name('employee_document.destroy');
