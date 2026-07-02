<?php

use App\Http\Controllers\RH\Leave\LeaveApprovalController;
use App\Http\Controllers\RH\Leave\LeavePlanController;
use App\Http\Controllers\RH\Leave\LeaveTypeController;
use App\Http\Controllers\RH\Leave\LeaveRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('leave-types')->group(function () {
    Route::get('/', [LeaveTypeController::class, 'index'])->name('leave_type.index');
    Route::post('/', [LeaveTypeController::class, 'store'])->name('leave_type.store');
    Route::get('{id}', [LeaveTypeController::class, 'show'])->name('leave_type.show');
    Route::put('{id}', [LeaveTypeController::class, 'update'])->name('leave_type.update');
    Route::delete('{id}', [LeaveTypeController::class, 'destroy'])->name('leave_type.destroy');
});

Route::prefix('leave-requests')->group(function () {
    Route::get('/', [LeaveRequestController::class, 'index'])->name('leave_request.index');
    Route::post('/', [LeaveRequestController::class, 'store'])->name('leave_request.store');
    Route::get('{id}', [LeaveRequestController::class, 'show'])->name('leave_request.show');
    Route::put('{id}', [LeaveRequestController::class, 'update'])->name('leave_request.update');
    Route::delete('{id}', [LeaveRequestController::class, 'destroy'])->name('leave_request.destroy');
    Route::get('{id}/balance', [LeaveRequestController::class, 'balance'])->name('leave_request.balance');
});

Route::prefix('plans')->group(function () {
    Route::get('/', [LeavePlanController::class, 'index'])->name('leave_plan.index');
    Route::post('/', [LeavePlanController::class, 'store'])->name('leave_plan.store');
    Route::get('{id}', [LeavePlanController::class, 'show'])->name('leave_plan.show');
    Route::put('{id}', [LeavePlanController::class, 'update'])->name('leave_plan.update');
    Route::delete('{id}', [LeavePlanController::class, 'destroy'])->name('leave_plan.destroy');
    Route::post('{id}/sync-balance', [LeavePlanController::class, 'syncBalance'])->name('leave_plan.sync');
});

Route::prefix('approvals')->group(function () {
    Route::get('pending', [LeaveApprovalController::class, 'pending'])->name('leave_approval.pending');
    Route::post('{leave_request_id}/approve', [LeaveApprovalController::class, 'approve'])->name('leave_approval.approve');
    Route::post('{leave_request_id}/reject', [LeaveApprovalController::class, 'reject'])->name('leave_approval.reject');
});

Route::get('calendar', [LeavePlanController::class, 'calendar'])->name('leave.calendar');
