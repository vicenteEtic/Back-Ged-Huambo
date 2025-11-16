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

Route::get('', [AlertController::class, 'index'])
    ->name('alert.index');

Route::get('/user/{id}', [AlertUserController::class, 'findByUser'])
    ->name('alertUser.show');

Route::get('/user', [AlertUserController::class, 'getAllUsersAlertSummary'])
    ->name('alertUser.getAllUsersAlertSummary');

Route::get('/user', [AlertUserController::class, 'getAllUsersAlertSummary'])
    ->name('alertUser.getAllUsersAlertSummary');

Route::post('/user', [AlertUserController::class, 'store'])
    ->name('alertUser.store');

Route::get('/total', [AlertController::class, 'getTotalAlerts'])
    ->name('alertUser.total');
Route::put('/user', [AlertUserController::class, 'update'])
    ->name('alertUser.update');


Route::get('/comment', [CommentAlertController::class, 'index'])
    ->name('comment.index');
Route::get('/comment/{id}', [CommentAlertController::class, 'show'])
    ->name('comment.show');
Route::post('/comment', [CommentAlertController::class, 'store'])
    ->name('comment.store');

Route::get('/me/notifications/', [AlertUserController::class, 'countActiveAlertsForAuthenticatedUser'])
    ->name('notifications.index');


Route::get('/grupoAlertEmails', [GrupoAlertEmailsController::class, 'index'])
    ->name('grupoAlertEmails.index');
Route::get('/grupoAlertEmails/{id}', [GrupoAlertEmailsController::class, 'show'])
    ->name('grupoAlertEmails.show');
Route::post('/grupoAlertEmails', [GrupoAlertEmailsController::class, 'store'])
    ->name('grupoAlertEmails.store');

Route::get('/grupoType', [GrupoTypeController::class, 'listTypGrup'])
    ->name('grupoType.listTypGrup');
Route::get('/grupoType/{id}', [GrupoTypeController::class, 'show'])
    ->name('grupoType.show');
Route::post('/grupoType', [GrupoTypeController::class, 'store'])
    ->name('grupoType.store');


Route::get('/userGrupo', [UserGrupoAlertController::class, 'index'])
    ->name('userGrupo.index');
Route::get('/userGrupo/{id}', [UserGrupoAlertController::class, 'show'])
    ->name('userGrupo.show');
Route::post('/userGrupo', [UserGrupoAlertController::class, 'store'])
    ->name('userGrupo.store');
Route::put('/{id}/status', [AlertController::class, 'status']);



Route::get('/files', [AlertAttachmentController::class, 'index'])
    ->name('alertAttachment.index');
Route::post('/files/{id}', [AlertAttachmentController::class, 'store'])
    ->name('alertAttachment.store');