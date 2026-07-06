<?php

use App\Http\Controllers\RH\Attendance\AttendanceController;
use App\Http\Controllers\RH\Attendance\ShiftAssignmentController;
use App\Http\Controllers\RH\Attendance\ShiftController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AttendanceController::class, 'index'])->name('attendance.root.index');
Route::post('/', [AttendanceController::class, 'store'])->name('attendance.root.store');

Route::prefix('shifts')->group(function () {
    Route::get('/', [ShiftController::class, 'index'])->name('shift.index');
    Route::post('/', [ShiftController::class, 'store'])->name('shift.store');
    Route::get('{id}', [ShiftController::class, 'show'])->name('shift.show');
    Route::put('{id}', [ShiftController::class, 'update'])->name('shift.update');
    Route::delete('{id}', [ShiftController::class, 'destroy'])->name('shift.destroy');
});

Route::prefix('assignments')->group(function () {
    Route::get('/', [ShiftAssignmentController::class, 'index'])->name('shift_assignment.index');
    Route::post('/', [ShiftAssignmentController::class, 'store'])->name('shift_assignment.store');
    Route::get('{id}', [ShiftAssignmentController::class, 'show'])->name('shift_assignment.show');
    Route::put('{id}', [ShiftAssignmentController::class, 'update'])->name('shift_assignment.update');
    Route::delete('{id}', [ShiftAssignmentController::class, 'destroy'])->name('shift_assignment.destroy');
});

Route::prefix('records')->group(function () {
    Route::get('/', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('{id}', [AttendanceController::class, 'show'])->name('attendance.show');
    Route::put('{id}', [AttendanceController::class, 'update'])->name('attendance.update');
    Route::delete('{id}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
});

Route::post('check-in', [AttendanceController::class, 'checkIn'])->name('attendance.checkin');
Route::post('check-out', [AttendanceController::class, 'checkOut'])->name('attendance.checkout');
Route::post('absence', [AttendanceController::class, 'absence'])->name('attendance.absence');
Route::post('import-biometric', [AttendanceController::class, 'importBiometric'])->name('attendance.import');
Route::get('reports/{employee_id}', [AttendanceController::class, 'monthlyReport'])->name('attendance.report');

Route::get('{id}', [AttendanceController::class, 'show'])->name('attendance.root.show');
Route::put('{id}', [AttendanceController::class, 'update'])->name('attendance.root.update');
Route::delete('{id}', [AttendanceController::class, 'destroy'])->name('attendance.root.destroy');
