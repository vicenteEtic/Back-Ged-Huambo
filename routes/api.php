<?php

use App\Http\Controllers\AlertAttachment\AlertAttachmentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\UserController;
Route::middleware(['auth:sanctum', 'auto.logout', 'track.activity'])->group(function () {
    Route::prefix('entities')->group(base_path('routes/entities/entities.php'));
    Route::prefix('diligence')->group(base_path('routes/diligence/diligence.php'));
    Route::prefix('indicator')->group(base_path('routes/indicator/indicator.php'));
    Route::prefix('permission')->group(base_path('routes/user/permission/permission.php'));
    Route::prefix('role')->group(base_path('routes/user/permission/role.php'));
    Route::prefix('indicator')->group(base_path('routes/indicator/indicator.php'));
    Route::prefix('user')->group(base_path('routes/user/user.php'));
    Route::prefix('risk_assessment')->group(base_path('routes/entities/risk_assessment.php'));
    Route::prefix('pep')->group(base_path('routes/entities/pep.php'));
    Route::prefix('alert')->group(base_path('routes/alert/alert.php'));
    Route::prefix('logs')->group(base_path('routes/logs/logs.php'));
    Route::prefix('dashboard')->group(base_path('routes/entities/dashboard.php'));
});
Route::post('/auth/login', [UserController::class, 'login']);
Route::prefix('auth')->middleware('guest')->group(base_path('routes/user/auth.php'));
Route::post('auth/2fa', [UserController::class, 'verify2fa']);

Route::middleware('web')->get('/alert/show/{id}/file', [AlertAttachmentController::class, 'showFile'])
    ->name('reports.showFile');