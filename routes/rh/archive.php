<?php

use App\Http\Controllers\RH\Archive\ArchiveCategoryController;
use App\Http\Controllers\RH\Archive\ArchiveDocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ArchiveDocumentController::class, 'index'])->name('archive.index')->middleware(['can:rh-arquivo-show']);
Route::post('/', [ArchiveDocumentController::class, 'store'])->name('archive.store')->middleware(['can:rh-arquivo-create']);

// === CATEGORIAS ===
Route::prefix('categories')->group(function () {
    Route::get('/', [ArchiveCategoryController::class, 'index'])->middleware(['can:rh-arquivo-show']);
    Route::post('/', [ArchiveCategoryController::class, 'store'])->middleware(['can:rh-arquivo-create']);
    Route::get('tree', [ArchiveCategoryController::class, 'tree'])->middleware(['can:rh-arquivo-show']);
    Route::get('by-type/{type}', [ArchiveCategoryController::class, 'byType'])->middleware(['can:rh-arquivo-show']);
    Route::get('{id}', [ArchiveCategoryController::class, 'show'])->middleware(['can:rh-arquivo-show']);
    Route::put('{id}', [ArchiveCategoryController::class, 'update'])->middleware(['can:rh-arquivo-edit']);
    Route::delete('{id}', [ArchiveCategoryController::class, 'destroy'])->middleware(['can:rh-arquivo-delete']);
});

// === DOCUMENTOS ===
Route::prefix('documents')->group(function () {
    Route::get('/', [ArchiveDocumentController::class, 'index'])->middleware(['can:rh-arquivo-show']);
    Route::post('/', [ArchiveDocumentController::class, 'store'])->middleware(['can:rh-arquivo-create']);
    Route::get('search', [ArchiveDocumentController::class, 'search'])->middleware(['can:rh-arquivo-show']);
    Route::get('by-employee/{employee_id}', [ArchiveDocumentController::class, 'byEmployee'])->middleware(['can:rh-arquivo-show']);
    Route::get('by-category/{category_id}', [ArchiveDocumentController::class, 'byCategory'])->middleware(['can:rh-arquivo-show']);
    Route::get('{id}', [ArchiveDocumentController::class, 'show'])->middleware(['can:rh-arquivo-show']);
    Route::get('{id}/file', [ArchiveDocumentController::class, 'showFile'])->middleware(['can:rh-arquivo-show']);
    Route::put('{id}', [ArchiveDocumentController::class, 'update'])->middleware(['can:rh-arquivo-edit']);
    Route::delete('{id}', [ArchiveDocumentController::class, 'destroy'])->middleware(['can:rh-arquivo-delete']);
    Route::post('{id}/approve', [ArchiveDocumentController::class, 'approve'])->middleware(['can:rh-arquivo-edit']);
    Route::post('{id}/archive', [ArchiveDocumentController::class, 'archive'])->middleware(['can:rh-arquivo-edit']);

    // Versões
    Route::get('{id}/versions', [ArchiveDocumentController::class, 'versions'])->middleware(['can:rh-arquivo-show']);
    Route::get('{id}/versions/{version_id}/file', [ArchiveDocumentController::class, 'showVersionFile'])->middleware(['can:rh-arquivo-show']);
    Route::post('{id}/versions', [ArchiveDocumentController::class, 'storeVersion'])->middleware(['can:rh-arquivo-create']);

    // Partilhas
    Route::get('{id}/shares', [ArchiveDocumentController::class, 'shares'])->middleware(['can:rh-arquivo-show']);
    Route::post('{id}/shares', [ArchiveDocumentController::class, 'storeShare'])->middleware(['can:rh-arquivo-create']);
    Route::delete('{id}/shares/{share_id}', [ArchiveDocumentController::class, 'destroyShare'])->middleware(['can:rh-arquivo-delete']);
});

Route::get('{id}', [ArchiveDocumentController::class, 'show'])->name('archive.show')->middleware(['can:rh-arquivo-show']);
Route::put('{id}', [ArchiveDocumentController::class, 'update'])->name('archive.update')->middleware(['can:rh-arquivo-edit']);
Route::delete('{id}', [ArchiveDocumentController::class, 'destroy'])->name('archive.destroy')->middleware(['can:rh-arquivo-delete']);
