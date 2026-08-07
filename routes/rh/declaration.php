<?php

use App\Http\Controllers\RH\Declaration\DeclarationRequestController;
use App\Http\Controllers\RH\Declaration\DeclarationTypeController;
use Illuminate\Support\Facades\Route;

Route::prefix('types')->group(function () {
    Route::get('/', [DeclarationTypeController::class, 'index'])->name('declaration_type.index')->middleware(['can:rh-declaracoes-show']);
    Route::post('/', [DeclarationTypeController::class, 'store'])->name('declaration_type.store')->middleware(['can:rh-declaracoes-create']);
    Route::get('{id}', [DeclarationTypeController::class, 'show'])->name('declaration_type.show')->middleware(['can:rh-declaracoes-show']);
    Route::put('{id}', [DeclarationTypeController::class, 'update'])->name('declaration_type.update')->middleware(['can:rh-declaracoes-edit']);
    Route::delete('{id}', [DeclarationTypeController::class, 'destroy'])->name('declaration_type.destroy')->middleware(['can:rh-declaracoes-delete']);
});

Route::get('pending', [DeclarationRequestController::class, 'pending'])->name('declaration.pending')->middleware(['can:rh-declaracoes-show']);
Route::get('preview', [DeclarationRequestController::class, 'preview'])->name('declaration.preview')->middleware(['can:rh-declaracoes-show']);

Route::get('/', [DeclarationRequestController::class, 'index'])->name('declaration.index')->middleware(['can:rh-declaracoes-show']);
Route::post('/', [DeclarationRequestController::class, 'store'])->name('declaration.store')->middleware(['can:rh-declaracoes-create']);

Route::get('{id}', [DeclarationRequestController::class, 'show'])->name('declaration.show')->middleware(['can:rh-declaracoes-show']);
Route::put('{id}', [DeclarationRequestController::class, 'update'])->name('declaration.update')->middleware(['can:rh-declaracoes-edit']);
Route::delete('{id}', [DeclarationRequestController::class, 'destroy'])->name('declaration.destroy')->middleware(['can:rh-declaracoes-delete']);

Route::get('{id}/preview', [DeclarationRequestController::class, 'previewRequest'])->name('declaration.preview_request')->middleware(['can:rh-declaracoes-show']);
Route::post('{id}/approve', [DeclarationRequestController::class, 'approve'])->name('declaration.approve')->middleware(['can:rh-declaracoes-edit']);
Route::post('{id}/reject', [DeclarationRequestController::class, 'reject'])->name('declaration.reject')->middleware(['can:rh-declaracoes-edit']);
Route::post('{id}/issue', [DeclarationRequestController::class, 'issue'])->name('declaration.issue')->middleware(['can:rh-declaracoes-edit']);
