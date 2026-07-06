<?php

use App\Http\Controllers\RH\Recruitment\JobOpeningController;
use App\Http\Controllers\RH\Recruitment\CandidateController;
use App\Http\Controllers\RH\Recruitment\ApplicationController;
use App\Http\Controllers\RH\Recruitment\InterviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [JobOpeningController::class, 'index'])->name('recruitment.index');
Route::post('/', [JobOpeningController::class, 'store'])->name('recruitment.store');

Route::prefix('job-openings')->group(function () {
    Route::get('/', [JobOpeningController::class, 'index'])->name('job_opening.index');
    Route::post('/', [JobOpeningController::class, 'store'])->name('job_opening.store');
    Route::get('{id}', [JobOpeningController::class, 'show'])->name('job_opening.show');
    Route::put('{id}', [JobOpeningController::class, 'update'])->name('job_opening.update');
    Route::delete('{id}', [JobOpeningController::class, 'destroy'])->name('job_opening.destroy');
});

Route::prefix('candidates')->group(function () {
    Route::get('/', [CandidateController::class, 'index'])->name('candidate.index');
    Route::post('/', [CandidateController::class, 'store'])->name('candidate.store');
    Route::get('{id}', [CandidateController::class, 'show'])->name('candidate.show');
    Route::put('{id}', [CandidateController::class, 'update'])->name('candidate.update');
    Route::delete('{id}', [CandidateController::class, 'destroy'])->name('candidate.destroy');
});

Route::prefix('applications')->group(function () {
    Route::get('/', [ApplicationController::class, 'index'])->name('application.index');
    Route::post('/', [ApplicationController::class, 'store'])->name('application.store');
    Route::get('{id}', [ApplicationController::class, 'show'])->name('application.show');
    Route::put('{id}', [ApplicationController::class, 'update'])->name('application.update');
    Route::delete('{id}', [ApplicationController::class, 'destroy'])->name('application.destroy');
});

Route::prefix('interviews')->group(function () {
    Route::get('/', [InterviewController::class, 'index'])->name('interview.index');
    Route::post('/', [InterviewController::class, 'store'])->name('interview.store');
    Route::get('{id}', [InterviewController::class, 'show'])->name('interview.show');
    Route::put('{id}', [InterviewController::class, 'update'])->name('interview.update');
    Route::delete('{id}', [InterviewController::class, 'destroy'])->name('interview.destroy');
});

Route::get('{id}', [JobOpeningController::class, 'show'])->name('recruitment.show');
Route::put('{id}', [JobOpeningController::class, 'update'])->name('recruitment.update');
Route::delete('{id}', [JobOpeningController::class, 'destroy'])->name('recruitment.destroy');
