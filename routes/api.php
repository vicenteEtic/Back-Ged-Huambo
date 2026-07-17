<?php

use App\Http\Controllers\AlertAttachment\AlertAttachmentController;
use App\Http\Controllers\Api\EnumController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\UserController;

Route::middleware(['auth:sanctum', 'auto.logout', 'track.activity'])->group(function () {
    Route::prefix('enums')->group(function () {
        Route::get('progression-types', [EnumController::class, 'progressionTypes']);
        Route::get('benefit-categories', [EnumController::class, 'benefitCategories']);
        Route::get('document-share-permissions', [EnumController::class, 'documentSharePermissions']);
        Route::get('document-statuses', [EnumController::class, 'documentStatuses']);
        Route::get('document-confidentialities', [EnumController::class, 'documentConfidentialities']);
        Route::get('attendance-statuses', [EnumController::class, 'attendanceStatuses']);
        Route::get('archive-category-types', [EnumController::class, 'archiveCategoryTypes']);
        Route::get('functional-history-types', [EnumController::class, 'functionalHistoryTypes']);
        Route::get('process-types', [EnumController::class, 'processTypes']);
        Route::get('process-statuses', [EnumController::class, 'processStatuses']);
        Route::get('process-classifications', [EnumController::class, 'processClassifications']);
        Route::get('process-priorities', [EnumController::class, 'processPriorities']);
        Route::get('process-visibilities', [EnumController::class, 'processVisibilities']);
        Route::get('process-assignment-statuses', [EnumController::class, 'processAssignmentStatuses']);
        Route::get('process-movement-types', [EnumController::class, 'processMovementTypes']);
        Route::get('department-types', [EnumController::class, 'departmentTypes']);
    });

    Route::prefix('permission')->group(base_path('routes/user/permission/permission.php'));
    Route::prefix('role')->group(base_path('routes/user/permission/role.php'));

    Route::prefix('user')->group(base_path('routes/user/user.php'));

    Route::prefix('logs')->group(base_path('routes/logs/logs.php'));

    Route::prefix('rh')->group(function () {
        Route::prefix('departments')->group(base_path('routes/rh/department.php'));
        Route::prefix('positions')->group(base_path('routes/rh/position.php'));
        Route::prefix('employees')->group(function () {
            Route::prefix('documents')->group(base_path('routes/rh/employee_document.php'));
            Route::group([], base_path('routes/rh/employee.php'));
        });
        
        Route::prefix('leaves')->group(base_path('routes/rh/leave.php'));
        Route::prefix('attendance')->group(base_path('routes/rh/attendance.php'));
        Route::prefix('payroll')->group(base_path('routes/rh/payroll.php'));
        Route::prefix('recruitment')->group(base_path('routes/rh/recruitment.php'));
        Route::prefix('training')->group(base_path('routes/rh/training.php'));
        Route::prefix('performance')->group(base_path('routes/rh/performance.php'));
        Route::prefix('benefits')->group(base_path('routes/rh/benefits.php'));
        Route::prefix('disciplinary')->group(base_path('routes/rh/disciplinary.php'));
        Route::prefix('functional-history')->group(base_path('routes/rh/functional_history.php'));
        Route::prefix('career')->group(base_path('routes/rh/career.php'));
        Route::prefix('progression')->group(base_path('routes/rh/career_progression.php'));
        Route::prefix('payslips')->group(base_path('routes/rh/payslip.php'));
        Route::prefix('benefits')->group(function () {
            Route::prefix('claims')->group(base_path('routes/rh/benefit_claims.php'));
            Route::prefix('medical')->group(base_path('routes/rh/medical_assistance.php'));
        });
        Route::prefix('retirement')->group(base_path('routes/rh/retirement.php'));
        Route::prefix('portal')->group(base_path('routes/rh/portal.php'));
        Route::prefix('archive')->group(base_path('routes/rh/archive.php'));
        Route::prefix('areas')->group(base_path('routes/rh/area.php'));
        Route::prefix('department-permissions')->group(base_path('routes/rh/department_permission.php'));
        Route::prefix('dashboard')->group(base_path('routes/rh/reports.php'));
    });

   // Route::prefix('processes/{process_id}/documents')->group(base_path('routes/process/process_document.php'));
    Route::prefix('processes')->group(base_path('routes/process/process.php'));
});
Route::post('/auth/login', [UserController::class, 'login']);
Route::prefix('auth')->middleware('guest')->group(base_path('routes/user/auth.php'));
//Route::post('auth/2fa', [UserController::class, 'verify2fa']);

