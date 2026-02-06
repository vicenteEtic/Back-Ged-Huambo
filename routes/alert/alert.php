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

Route::get('/total', [AlertController::class, 'getTotalAlerts'])
    ->name('alertUser.total');
Route::put('/user', [AlertUserController::class, 'update'])
    ->name('alertUser.update');


Route::get('/comment', [CommentAlertController::class, 'index'])
    ->name('comment.index');
Route::get('/comment/{id}', [CommentAlertController::class, 'show'])<
   
Route::post('/comment', [CommentAlertController::class, 'store'])
    ->name('comment.store');

Route::get('/me/notifications/', [AlertUserController::class, 'countActiveAlertsForAuthenticatedUser'])
    ->name('notifications.index');


Route::get('/grupoAlertEmails', [GrupoAlertEmailsController::class, 'index'])
    ->name('grupoAlertEmails.index');
Route::get('/grupoAlertEmails/{id}', [GrupoAlertEmailsController::class, 'show'])
;
Route::post('/grupoAlertEmails', [GrupoAlertEmailsController::class, 'store'])
    ->name('grupoAlertEmails.store');

    Route::put('/grupoAlertEmails/{id}', [GrupoAlertEmailsController::class, 'update']);

Route::put('/grupoAlertEmails/{id}', [GrupoAlertEmailsController::class, 'update']);

Route::get('/grupoType', [GrupoTypeController::class, 'listTypGrup'])
    ->name('grupoType.listTypGrup');
Route::get('/grupoType/{id}', [GrupoTypeController::class, 'show'])
    ;
Route::post('/grupoType', [GrupoTypeController::class, 'store'])
    ->name('grupoType.store');
    Route::put('/grupoType/{id}', [GrupoTypeController::class, 'update']);

Route::get('/userGrupo', [UserGrupoAlertController::class, 'index'])
    ->name('userGrupo.index');
Route::get('/userGrupo/{id}', [UserGrupoAlertController::class, 'show'])
   ;
Route::post('/userGrupo', [UserGrupoAlertController::class, 'store'])
    ->name('userGrupo.store');
Route::put('/{id}/status', [AlertController::class, 'status']);
Route::put('/userGrupo/{id}', [UserGrupoAlertController::class, 'update'])
;

Route::get('/files/{alertID}', [AlertAttachmentController::class, 'files'])
    ->name('alertAttachment.index');
Route::post('/files/{idAlert}', [AlertAttachmentController::class, 'store'])
    ->name('alertAttachment.store');;
    Route::get('/files/showFile/{id}', [AlertAttachmentController::class, 'showFile'])
    ->name('alertAttachment.showFile');;
