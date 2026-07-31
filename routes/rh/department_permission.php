<?php

use App\Http\Controllers\RH\Department\DepartmentPermissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DepartmentPermissionController::class, 'index'])->name('department-permission.index')->middleware(['can:rh-permissoes-departamento-show']);
Route::post('/', [DepartmentPermissionController::class, 'store'])->name('department-permission.store')->middleware(['can:rh-permissoes-departamento-create']);
Route::get('{id}', [DepartmentPermissionController::class, 'show'])->name('department-permission.show')->middleware(['can:rh-permissoes-departamento-show']);
Route::delete('{id}', [DepartmentPermissionController::class, 'destroy'])->name('department-permission.destroy')->middleware(['can:rh-permissoes-departamento-delete']);
Route::get('by-department/{departmentId}', [DepartmentPermissionController::class, 'byDepartment'])->name('department-permission.byDepartment')->middleware(['can:rh-permissoes-departamento-show']);
