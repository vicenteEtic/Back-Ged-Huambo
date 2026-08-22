<?php

use App\Http\Controllers\RH\Category\CategoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CategoryController::class, 'index'])->name('category.index')->middleware(['can:rh-categorias-show']);
Route::post('/', [CategoryController::class, 'store'])->name('category.store')->middleware(['can:rh-categorias-create']);
Route::get('{id}', [CategoryController::class, 'show'])->name('category.show')->middleware(['can:rh-categorias-show']);
Route::put('{id}', [CategoryController::class, 'update'])->name('category.update')->middleware(['can:rh-categorias-edit']);
Route::delete('{id}', [CategoryController::class, 'destroy'])->name('category.destroy')->middleware(['can:rh-categorias-delete']);
