<?php

use App\Http\Controllers\RH\Attendance\AbsenceJustificationController;
use App\Http\Controllers\RH\Attendance\AttendanceController;
use App\Http\Controllers\RH\Attendance\ShiftAssignmentController;
use App\Http\Controllers\RH\Attendance\ShiftController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AttendanceController::class, 'index'])->name('attendance.root.index')->middleware(['can:rh-ponto-show']);
Route::post('/', [AttendanceController::class, 'store'])->name('attendance.root.store')->middleware(['can:rh-ponto-create']);

Route::prefix('shifts')->group(function () {
    Route::get('/', [ShiftController::class, 'index'])->name('shift.index')->middleware(['can:rh-ponto-show']);
    Route::post('/', [ShiftController::class, 'store'])->name('shift.store')->middleware(['can:rh-ponto-create']);
    Route::get('{id}', [ShiftController::class, 'show'])->name('shift.show')->middleware(['can:rh-ponto-show']);
    Route::put('{id}', [ShiftController::class, 'update'])->name('shift.update')->middleware(['can:rh-ponto-edit']);
    Route::delete('{id}', [ShiftController::class, 'destroy'])->name('shift.destroy')->middleware(['can:rh-ponto-delete']);
});

Route::prefix('assignments')->group(function () {
    Route::get('/', [ShiftAssignmentController::class, 'index'])->name('shift_assignment.index')->middleware(['can:rh-ponto-show']);
    Route::post('/', [ShiftAssignmentController::class, 'store'])->name('shift_assignment.store')->middleware(['can:rh-ponto-create']);
    Route::get('{id}', [ShiftAssignmentController::class, 'show'])->name('shift_assignment.show')->middleware(['can:rh-ponto-show']);
    Route::put('{id}', [ShiftAssignmentController::class, 'update'])->name('shift_assignment.update')->middleware(['can:rh-ponto-edit']);
    Route::delete('{id}', [ShiftAssignmentController::class, 'destroy'])->name('shift_assignment.destroy')->middleware(['can:rh-ponto-delete']);
});

Route::prefix('records')->group(function () {
    Route::get('/', [AttendanceController::class, 'index'])->name('attendance.index')->middleware(['can:rh-ponto-show']);
    Route::post('/', [AttendanceController::class, 'store'])->name('attendance.store')->middleware(['can:rh-ponto-create']);
    Route::get('{id}', [AttendanceController::class, 'show'])->name('attendance.show')->middleware(['can:rh-ponto-show']);
    Route::put('{id}', [AttendanceController::class, 'update'])->name('attendance.update')->middleware(['can:rh-ponto-edit']);
    Route::delete('{id}', [AttendanceController::class, 'destroy'])->name('attendance.destroy')->middleware(['can:rh-ponto-delete']);
});

Route::post('check-in', [AttendanceController::class, 'checkIn'])->name('attendance.checkin')->middleware(['can:rh-ponto-create']);
Route::post('check-out', [AttendanceController::class, 'checkOut'])->name('attendance.checkout')->middleware(['can:rh-ponto-create']);
Route::post('import-biometric', [AttendanceController::class, 'importBiometric'])->name('attendance.import')->middleware(['can:rh-ponto-create']);
Route::get('absences', [AttendanceController::class, 'absences'])->name('attendance.absences')->middleware(['can:rh-ponto-show']);

Route::prefix('absences/justifications')->group(function () {
    Route::get('/', [AbsenceJustificationController::class, 'index'])->name('attendance.absence_justification.index')->middleware(['can:rh-ponto-show']);
    Route::post('/', [AbsenceJustificationController::class, 'store'])->name('attendance.absence_justification.store')->middleware(['can:rh-ponto-create']);
    Route::get('{id}', [AbsenceJustificationController::class, 'show'])->name('attendance.absence_justification.show')->middleware(['can:rh-ponto-show']);
    Route::put('{id}', [AbsenceJustificationController::class, 'update'])->name('attendance.absence_justification.update')->middleware(['can:rh-ponto-edit']);
    Route::delete('{id}', [AbsenceJustificationController::class, 'destroy'])->name('attendance.absence_justification.destroy')->middleware(['can:rh-ponto-delete']);
    Route::post('{id}/approve', [AbsenceJustificationController::class, 'approve'])->name('attendance.absence_justification.approve')->middleware(['can:rh-ponto-edit']);
    Route::post('{id}/reject', [AbsenceJustificationController::class, 'reject'])->name('attendance.absence_justification.reject')->middleware(['can:rh-ponto-edit']);
    Route::get('{id}/proof', [AbsenceJustificationController::class, 'downloadProof'])->name('attendance.absence_justification.proof')->middleware(['can:rh-ponto-show']);
});

Route::get('reports/{employee_id}', [AttendanceController::class, 'monthlyReport'])->name('attendance.report')->middleware(['can:rh-ponto-show']);

Route::get('{id}', [AttendanceController::class, 'show'])->name('attendance.root.show')->middleware(['can:rh-ponto-show']);
Route::put('{id}', [AttendanceController::class, 'update'])->name('attendance.root.update')->middleware(['can:rh-ponto-edit']);
Route::delete('{id}', [AttendanceController::class, 'destroy'])->name('attendance.root.destroy')->middleware(['can:rh-ponto-delete']);
