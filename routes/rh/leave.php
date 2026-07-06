<?php

use App\Http\Controllers\RH\Leave\LeaveApprovalController;
use App\Http\Controllers\RH\Leave\LeavePlanController;
use App\Http\Controllers\RH\Leave\LeaveTypeController;
use App\Http\Controllers\RH\Leave\LeaveRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LeaveRequestController::class, 'index'])->name('leave.index')->middleware(['can:rh-ferias-show']);
Route::post('/', [LeaveRequestController::class, 'store'])->name('leave.store')->middleware(['can:rh-ferias-create']);

Route::prefix('leave-types')->group(function () {
    Route::get('/', [LeaveTypeController::class, 'index'])->name('leave_type.index')->middleware(['can:rh-ferias-show']);
    Route::post('/', [LeaveTypeController::class, 'store'])->name('leave_type.store')->middleware(['can:rh-ferias-create']);
    Route::get('{id}', [LeaveTypeController::class, 'show'])->name('leave_type.show')->middleware(['can:rh-ferias-show']);
    Route::put('{id}', [LeaveTypeController::class, 'update'])->name('leave_type.update')->middleware(['can:rh-ferias-edit']);
    Route::delete('{id}', [LeaveTypeController::class, 'destroy'])->name('leave_type.destroy')->middleware(['can:rh-ferias-delete']);
});

Route::prefix('leave-requests')->group(function () {
    Route::get('/', [LeaveRequestController::class, 'index'])->name('leave_request.index')->middleware(['can:rh-ferias-show']);
    Route::post('/', [LeaveRequestController::class, 'store'])->name('leave_request.store')->middleware(['can:rh-ferias-create']);
    Route::get('{id}', [LeaveRequestController::class, 'show'])->name('leave_request.show')->middleware(['can:rh-ferias-show']);
    Route::put('{id}', [LeaveRequestController::class, 'update'])->name('leave_request.update')->middleware(['can:rh-ferias-edit']);
    Route::delete('{id}', [LeaveRequestController::class, 'destroy'])->name('leave_request.destroy')->middleware(['can:rh-ferias-delete']);
    Route::get('{id}/balance', [LeaveRequestController::class, 'balance'])->name('leave_request.balance')->middleware(['can:rh-ferias-show']);
});

Route::prefix('plans')->group(function () {
    Route::get('/', [LeavePlanController::class, 'index'])->name('leave_plan.index')->middleware(['can:rh-ferias-show']);
    Route::post('/', [LeavePlanController::class, 'store'])->name('leave_plan.store')->middleware(['can:rh-ferias-create']);
    Route::get('{id}', [LeavePlanController::class, 'show'])->name('leave_plan.show')->middleware(['can:rh-ferias-show']);
    Route::put('{id}', [LeavePlanController::class, 'update'])->name('leave_plan.update')->middleware(['can:rh-ferias-edit']);
    Route::delete('{id}', [LeavePlanController::class, 'destroy'])->name('leave_plan.destroy')->middleware(['can:rh-ferias-delete']);
    Route::post('{id}/sync-balance', [LeavePlanController::class, 'syncBalance'])->name('leave_plan.sync')->middleware(['can:rh-ferias-edit']);
});

Route::prefix('approvals')->group(function () {
    Route::get('pending', [LeaveApprovalController::class, 'pending'])->name('leave_approval.pending')->middleware(['can:rh-ferias-show']);
    Route::post('{leave_request_id}/approve', [LeaveApprovalController::class, 'approve'])->name('leave_approval.approve')->middleware(['can:rh-ferias-edit']);
    Route::post('{leave_request_id}/reject', [LeaveApprovalController::class, 'reject'])->name('leave_approval.reject')->middleware(['can:rh-ferias-edit']);
});

Route::get('calendar', [LeavePlanController::class, 'calendar'])->name('leave.calendar')->middleware(['can:rh-ferias-show']);

Route::get('{id}', [LeaveRequestController::class, 'show'])->name('leave.show')->middleware(['can:rh-ferias-show']);
Route::put('{id}', [LeaveRequestController::class, 'update'])->name('leave.update')->middleware(['can:rh-ferias-edit']);
Route::delete('{id}', [LeaveRequestController::class, 'destroy'])->name('leave.destroy')->middleware(['can:rh-ferias-delete']);
