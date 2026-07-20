<?php

use App\Http\Controllers\RH\EmployeeDocument\EmployeeDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EmployeeDocumentController::class, 'index'])->name('employee_document.index')->middleware(['can:rh-documentos-show']);
Route::post('/', [EmployeeDocumentController::class, 'store'])->name('employee_document.store')->middleware(['can:rh-documentos-create']);
Route::get('/{id}', [EmployeeDocumentController::class, 'show'])->name('employee_document.show')->middleware(['can:rh-documentos-show']);
Route::get('/{id}/file', [EmployeeDocumentController::class, 'showFile'])->name('employee_document.file')->middleware(['can:rh-documentos-show']);
Route::put('/{id}', [EmployeeDocumentController::class, 'update'])->name('employee_document.update')->middleware(['can:rh-documentos-edit']);
Route::delete('/{id}', [EmployeeDocumentController::class, 'destroy'])->name('employee_document.destroy')->middleware(['can:rh-documentos-delete']);