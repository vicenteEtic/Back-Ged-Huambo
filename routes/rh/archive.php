<?php

use App\Http\Controllers\RH\Archive\ArchiveCategoryController;
use App\Http\Controllers\RH\Archive\ArchiveDocumentController;
use Illuminate\Support\Facades\Route;

// === CATEGORIAS ===
Route::prefix('categories')->group(function () {
    Route::get('/', [ArchiveCategoryController::class, 'index']);
    Route::post('/', [ArchiveCategoryController::class, 'store']);
    Route::get('tree', [ArchiveCategoryController::class, 'tree']);
    Route::get('by-type/{type}', [ArchiveCategoryController::class, 'byType']);
    Route::get('{id}', [ArchiveCategoryController::class, 'show']);
    Route::put('{id}', [ArchiveCategoryController::class, 'update']);
    Route::delete('{id}', [ArchiveCategoryController::class, 'destroy']);
});

// === DOCUMENTOS ===
Route::prefix('documents')->group(function () {
    Route::get('/', [ArchiveDocumentController::class, 'index']);
    Route::post('/', [ArchiveDocumentController::class, 'store']);
    Route::get('search', [ArchiveDocumentController::class, 'search']);
    Route::get('by-employee/{employee_id}', [ArchiveDocumentController::class, 'byEmployee']);
    Route::get('by-category/{category_id}', [ArchiveDocumentController::class, 'byCategory']);
    Route::get('{id}', [ArchiveDocumentController::class, 'show']);
    Route::put('{id}', [ArchiveDocumentController::class, 'update']);
    Route::delete('{id}', [ArchiveDocumentController::class, 'destroy']);
    Route::post('{id}/approve', [ArchiveDocumentController::class, 'approve']);
    Route::post('{id}/archive', [ArchiveDocumentController::class, 'archive']);

    // Versões
    Route::get('{id}/versions', [ArchiveDocumentController::class, 'versions']);
    Route::post('{id}/versions', [ArchiveDocumentController::class, 'storeVersion']);

    // Partilhas
    Route::get('{id}/shares', [ArchiveDocumentController::class, 'shares']);
    Route::post('{id}/shares', [ArchiveDocumentController::class, 'storeShare']);
    Route::delete('{id}/shares/{share_id}', [ArchiveDocumentController::class, 'destroyShare']);
});
