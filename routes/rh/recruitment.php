<?php

use App\Http\Controllers\RH\Recruitment\JobOpeningController;
use App\Http\Controllers\RH\Recruitment\CandidateController;
use App\Http\Controllers\RH\Recruitment\ApplicationController;
use App\Http\Controllers\RH\Recruitment\InterviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [JobOpeningController::class, 'index'])->name('recruitment.index')->middleware(['can:rh-recrutamento-show']);
Route::post('/', [JobOpeningController::class, 'store'])->name('recruitment.store')->middleware(['can:rh-recrutamento-create']);

Route::prefix('job-openings')->group(function () {
    Route::get('/', [JobOpeningController::class, 'index'])->name('job_opening.index')->middleware(['can:rh-recrutamento-show']);
    Route::post('/', [JobOpeningController::class, 'store'])->name('job_opening.store')->middleware(['can:rh-recrutamento-create']);
    Route::get('{id}', [JobOpeningController::class, 'show'])->name('job_opening.show')->middleware(['can:rh-recrutamento-show']);
    Route::put('{id}', [JobOpeningController::class, 'update'])->name('job_opening.update')->middleware(['can:rh-recrutamento-edit']);
    Route::delete('{id}', [JobOpeningController::class, 'destroy'])->name('job_opening.destroy')->middleware(['can:rh-recrutamento-delete']);
});

Route::prefix('candidates')->group(function () {
    Route::get('/', [CandidateController::class, 'index'])->name('candidate.index')->middleware(['can:rh-recrutamento-show']);
    Route::post('/', [CandidateController::class, 'store'])->name('candidate.store')->middleware(['can:rh-recrutamento-create']);
    Route::get('{id}', [CandidateController::class, 'show'])->name('candidate.show')->middleware(['can:rh-recrutamento-show']);
    Route::put('{id}', [CandidateController::class, 'update'])->name('candidate.update')->middleware(['can:rh-recrutamento-edit']);
    Route::delete('{id}', [CandidateController::class, 'destroy'])->name('candidate.destroy')->middleware(['can:rh-recrutamento-delete']);
});

Route::prefix('applications')->group(function () {
    Route::get('/', [ApplicationController::class, 'index'])->name('application.index')->middleware(['can:rh-recrutamento-show']);
    Route::post('/', [ApplicationController::class, 'store'])->name('application.store')->middleware(['can:rh-recrutamento-create']);
    Route::get('{id}', [ApplicationController::class, 'show'])->name('application.show')->middleware(['can:rh-recrutamento-show']);
    Route::put('{id}', [ApplicationController::class, 'update'])->name('application.update')->middleware(['can:rh-recrutamento-edit']);
    Route::delete('{id}', [ApplicationController::class, 'destroy'])->name('application.destroy')->middleware(['can:rh-recrutamento-delete']);
});

Route::prefix('interviews')->group(function () {
    Route::get('/', [InterviewController::class, 'index'])->name('interview.index')->middleware(['can:rh-recrutamento-show']);
    Route::post('/', [InterviewController::class, 'store'])->name('interview.store')->middleware(['can:rh-recrutamento-create']);
    Route::get('{id}', [InterviewController::class, 'show'])->name('interview.show')->middleware(['can:rh-recrutamento-show']);
    Route::put('{id}', [InterviewController::class, 'update'])->name('interview.update')->middleware(['can:rh-recrutamento-edit']);
    Route::delete('{id}', [InterviewController::class, 'destroy'])->name('interview.destroy')->middleware(['can:rh-recrutamento-delete']);
});

Route::get('{id}', [JobOpeningController::class, 'show'])->name('recruitment.show')->middleware(['can:rh-recrutamento-show']);
Route::put('{id}', [JobOpeningController::class, 'update'])->name('recruitment.update')->middleware(['can:rh-recrutamento-edit']);
Route::delete('{id}', [JobOpeningController::class, 'destroy'])->name('recruitment.destroy')->middleware(['can:rh-recrutamento-delete']);
