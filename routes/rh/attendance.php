<?php

use App\Http\Controllers\RH\Attendance\AbsenceJustificationController;
use App\Http\Controllers\RH\Attendance\AbsenceTypeController;
use App\Http\Controllers\RH\Attendance\AttendanceController;
use App\Http\Controllers\RH\Attendance\AttendanceReportController;
use App\Http\Controllers\RH\Attendance\AttendanceRequestController;
use App\Http\Controllers\RH\Attendance\AttendanceRequestTypeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AttendanceController::class, 'index'])->name('attendance.root.index')->middleware(['can:rh-ponto-show']);
Route::post('/', [AttendanceController::class, 'store'])->name('attendance.root.store')->middleware(['can:rh-ponto-create']);

Route::prefix('absence-types')->group(function () {
    Route::get('/', [AbsenceTypeController::class, 'index'])->name('absence_type.index')->middleware(['can:rh-ponto-show']);
    Route::post('/', [AbsenceTypeController::class, 'store'])->name('absence_type.store')->middleware(['can:rh-ponto-create']);
    Route::get('{id}', [AbsenceTypeController::class, 'show'])->name('absence_type.show')->middleware(['can:rh-ponto-show']);
    Route::put('{id}', [AbsenceTypeController::class, 'update'])->name('absence_type.update')->middleware(['can:rh-ponto-edit']);
    Route::delete('{id}', [AbsenceTypeController::class, 'destroy'])->name('absence_type.destroy')->middleware(['can:rh-ponto-delete']);
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
Route::get('employees-for-point', [AttendanceController::class, 'employeesForPoint'])->name('attendance.employees_for_point')->middleware(['can:rh-ponto-create']);
Route::prefix('shifts')->group(function () {
    Route::get('/', [AttendanceController::class, 'removedFeature'])->name('attendance.shifts.index.compat')->middleware(['can:rh-ponto-show']);
    Route::post('/', [AttendanceController::class, 'removedStore'])->name('attendance.shifts.store.compat')->middleware(['can:rh-ponto-create']);
    Route::get('{id}', [AttendanceController::class, 'removedShow'])->name('attendance.shifts.show.compat')->middleware(['can:rh-ponto-show']);
    Route::put('{id}', [AttendanceController::class, 'removedUpdate'])->name('attendance.shifts.update.compat')->middleware(['can:rh-ponto-edit']);
    Route::delete('{id}', [AttendanceController::class, 'removedDestroy'])->name('attendance.shifts.destroy.compat')->middleware(['can:rh-ponto-delete']);
});

Route::prefix('assignments')->group(function () {
    Route::get('/', [AttendanceController::class, 'removedFeature'])->name('attendance.assignments.index.compat')->middleware(['can:rh-ponto-show']);
    Route::post('/', [AttendanceController::class, 'removedStore'])->name('attendance.assignments.store.compat')->middleware(['can:rh-ponto-create']);
    Route::get('{id}', [AttendanceController::class, 'removedShow'])->name('attendance.assignments.show.compat')->middleware(['can:rh-ponto-show']);
    Route::put('{id}', [AttendanceController::class, 'removedUpdate'])->name('attendance.assignments.update.compat')->middleware(['can:rh-ponto-edit']);
    Route::delete('{id}', [AttendanceController::class, 'removedDestroy'])->name('attendance.assignments.destroy.compat')->middleware(['can:rh-ponto-delete']);
});
Route::get('absences/types', [AttendanceController::class, 'absenceTypes'])->name('attendance.absence_types')->middleware(['can:rh-ponto-show']);
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
Route::get('employees/{employee_id}/assiduidade', [AttendanceController::class, 'employeeAssiduidade'])->name('attendance.employee_assiduidade')->middleware(['can:rh-ponto-show']);

Route::prefix('report')->group(function () {
    Route::get('/', [AttendanceReportController::class, 'data'])->name('attendance.report.data')->middleware(['can:rh-ponto-show']);
    Route::get('download', [AttendanceReportController::class, 'download'])->name('attendance.report.download')->middleware(['can:rh-ponto-show']);
    Route::get('employee/{employee_id}', [AttendanceReportController::class, 'data'])->name('attendance.report.employee')->middleware(['can:rh-ponto-show']);
    Route::get('employee/{employee_id}/download', [AttendanceReportController::class, 'download'])->name('attendance.report.employee_download')->middleware(['can:rh-ponto-show']);
});

$dispatchPrefixes = ['requests', 'solicitacoes'];

foreach ($dispatchPrefixes as $dispatchPrefix) {
    $base = $dispatchPrefix === 'requests' ? 'attendance_request' : 'attendance_request.'.$dispatchPrefix;
    $typeBase = $base.'.type';

    Route::prefix($dispatchPrefix)->group(function () use ($base, $typeBase) {
        Route::get('metadata', [AttendanceRequestController::class, 'metadata'])->name($base.'.metadata')->middleware(['can:rh-dispensas-show']);

        Route::prefix('tipos')->group(function () use ($typeBase) {
            Route::get('/', [AttendanceRequestTypeController::class, 'index'])->name($typeBase.'.index')->middleware(['can:rh-dispensas-show']);
            Route::post('/', [AttendanceRequestTypeController::class, 'store'])->name($typeBase.'.store')->middleware(['can:rh-dispensas-create']);
            Route::get('{id}', [AttendanceRequestTypeController::class, 'show'])->name($typeBase.'.show')->middleware(['can:rh-dispensas-show']);
            Route::put('{id}', [AttendanceRequestTypeController::class, 'update'])->name($typeBase.'.update')->middleware(['can:rh-dispensas-edit']);
            Route::delete('{id}', [AttendanceRequestTypeController::class, 'destroy'])->name($typeBase.'.destroy')->middleware(['can:rh-dispensas-delete']);
        });

        Route::get('/', [AttendanceRequestController::class, 'index'])->name($base.'.index')->middleware(['can:rh-dispensas-show']);
        Route::post('/', [AttendanceRequestController::class, 'store'])->name($base.'.store')->middleware(['can:rh-dispensas-create']);
        Route::get('{id}', [AttendanceRequestController::class, 'show'])->name($base.'.show')->middleware(['can:rh-dispensas-show']);
        Route::put('{id}', [AttendanceRequestController::class, 'update'])->name($base.'.update')->middleware(['can:rh-dispensas-edit']);
        Route::delete('{id}', [AttendanceRequestController::class, 'destroy'])->name($base.'.destroy')->middleware(['can:rh-dispensas-delete']);
        Route::post('{id}/under-review', [AttendanceRequestController::class, 'underReview'])->name($base.'.under_review')->middleware(['can:rh-dispensas-underreview']);
        Route::post('{id}/approve', [AttendanceRequestController::class, 'approve'])->name($base.'.approve')->middleware(['can:rh-dispensas-approve']);
        Route::post('{id}/reject', [AttendanceRequestController::class, 'reject'])->name($base.'.reject')->middleware(['can:rh-dispensas-reject']);
        Route::post('{id}/cancel', [AttendanceRequestController::class, 'cancel'])->name($base.'.cancel')->middleware(['can:rh-dispensas-cancel']);
        Route::get('{id}/despacho', [AttendanceRequestController::class, 'despacho'])->name($base.'.despacho')->middleware(['can:rh-dispensas-despacho']);
        Route::get('{id}/despacho/download', [AttendanceRequestController::class, 'downloadDespacho'])->name($base.'.despacho_download')->middleware(['can:rh-dispensas-despacho']);
        Route::get('{id}/documents/{documentId}/download', [AttendanceRequestController::class, 'downloadDocument'])->name($base.'.document_download')->middleware(['can:rh-dispensas-show']);
    });
}

Route::get('{id}', [AttendanceController::class, 'show'])->name('attendance.root.show')->middleware(['can:rh-ponto-show']);
Route::put('{id}', [AttendanceController::class, 'update'])->name('attendance.root.update')->middleware(['can:rh-ponto-edit']);
Route::delete('{id}', [AttendanceController::class, 'destroy'])->name('attendance.root.destroy')->middleware(['can:rh-ponto-delete']);
