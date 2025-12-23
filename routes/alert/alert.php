<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Alert\AlertController;
use App\Http\Controllers\Alert\AlertUser\AlertUserController;
use App\Http\Controllers\Alert\CommentAlert\CommentAlertController;
use App\Http\Controllers\Alert\GrupoAlertEmails\GrupoAlertEmailsController;
use App\Http\Controllers\Alert\GrupoType\GrupoTypeController;
use App\Http\Controllers\Alert\UserGrupoAlert\UserGrupoAlertController;
use App\Http\Controllers\AlertAttachment\AlertAttachmentController;
use App\Http\Requests\Alert\CommentAlert\CommentAlertRequest;
use App\Models\Alert\AlertUser\AlertUser;

    Route::get('/', [AlertController::class, 'index']);
    Route::get('/total', [AlertController::class, 'getTotalAlerts']);
    Route::put('/{id}/status', [AlertController::class, 'status']);

    Route::apiResource('grupoAlertEmails', GrupoAlertEmailsController::class);
    Route::apiResource('grupoType', GrupoTypeController::class);
    Route::apiResource('userGrupo', UserGrupoAlertController::class);

    Route::get('/user/{id}', [AlertUserController::class, 'findByUser']);
    Route::get('/user', [AlertUserController::class, 'getAllUsersAlertSummary']);
    Route::post('/user', [AlertUserController::class, 'store']);
    Route::put('/user/{id}', [AlertUserController::class, 'update']);

    Route::apiResource('comment', CommentAlertController::class)->only([
        'index', 'show', 'store'
    ]);

    Route::get('/files', [AlertAttachmentController::class, 'index']);
    Route::post('/files/{id}', [AlertAttachmentController::class, 'store']);

    Route::get('/me/notifications', [AlertUserController::class, 'countActiveAlertsForAuthenticatedUser']);