<?php

use App\Http\Controllers\RH\EmployeeDocument\DocumentTypeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DocumentTypeController::class, 'index'])->name('document_type.index')->middleware(['can:rh-tipos-de-documento-show']);
Route::post('/', [DocumentTypeController::class, 'store'])->name('document_type.store')->middleware(['can:rh-tipos-de-documento-create']);
Route::get('{id}', [DocumentTypeController::class, 'show'])->name('document_type.show')->middleware(['can:rh-tipos-de-documento-show']);
Route::put('{id}', [DocumentTypeController::class, 'update'])->name('document_type.update')->middleware(['can:rh-tipos-de-documento-edit']);
Route::delete('{id}', [DocumentTypeController::class, 'destroy'])->name('document_type.destroy')->middleware(['can:rh-tipos-de-documento-delete']);
