<?php

use App\Http\Controllers\RH\Training\TrainingCourseController;
use App\Http\Controllers\RH\Training\TrainingSessionController;
use App\Http\Controllers\RH\Training\TrainingEnrollmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TrainingCourseController::class, 'index'])->name('training.index');
Route::post('/', [TrainingCourseController::class, 'store'])->name('training.store');

Route::prefix('courses')->group(function () {
    Route::get('/', [TrainingCourseController::class, 'index'])->name('training_course.index');
    Route::post('/', [TrainingCourseController::class, 'store'])->name('training_course.store');
    Route::get('{id}', [TrainingCourseController::class, 'show'])->name('training_course.show');
    Route::put('{id}', [TrainingCourseController::class, 'update'])->name('training_course.update');
    Route::delete('{id}', [TrainingCourseController::class, 'destroy'])->name('training_course.destroy');
});

Route::prefix('sessions')->group(function () {
    Route::get('/', [TrainingSessionController::class, 'index'])->name('training_session.index');
    Route::post('/', [TrainingSessionController::class, 'store'])->name('training_session.store');
    Route::get('{id}', [TrainingSessionController::class, 'show'])->name('training_session.show');
    Route::put('{id}', [TrainingSessionController::class, 'update'])->name('training_session.update');
    Route::delete('{id}', [TrainingSessionController::class, 'destroy'])->name('training_session.destroy');
});

Route::prefix('enrollments')->group(function () {
    Route::get('/', [TrainingEnrollmentController::class, 'index'])->name('training_enrollment.index');
    Route::post('/', [TrainingEnrollmentController::class, 'store'])->name('training_enrollment.store');
    Route::get('{id}', [TrainingEnrollmentController::class, 'show'])->name('training_enrollment.show');
    Route::put('{id}', [TrainingEnrollmentController::class, 'update'])->name('training_enrollment.update');
    Route::delete('{id}', [TrainingEnrollmentController::class, 'destroy'])->name('training_enrollment.destroy');
});

Route::get('{id}', [TrainingCourseController::class, 'show'])->name('training.show');
Route::put('{id}', [TrainingCourseController::class, 'update'])->name('training.update');
Route::delete('{id}', [TrainingCourseController::class, 'destroy'])->name('training.destroy');
