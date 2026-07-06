<?php

use App\Http\Controllers\RH\Training\TrainingCourseController;
use App\Http\Controllers\RH\Training\TrainingSessionController;
use App\Http\Controllers\RH\Training\TrainingEnrollmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TrainingCourseController::class, 'index'])->name('training.index')->middleware(['can:rh-formacao-show']);
Route::post('/', [TrainingCourseController::class, 'store'])->name('training.store')->middleware(['can:rh-formacao-create']);

Route::prefix('courses')->group(function () {
    Route::get('/', [TrainingCourseController::class, 'index'])->name('training_course.index')->middleware(['can:rh-formacao-show']);
    Route::post('/', [TrainingCourseController::class, 'store'])->name('training_course.store')->middleware(['can:rh-formacao-create']);
    Route::get('{id}', [TrainingCourseController::class, 'show'])->name('training_course.show')->middleware(['can:rh-formacao-show']);
    Route::put('{id}', [TrainingCourseController::class, 'update'])->name('training_course.update')->middleware(['can:rh-formacao-edit']);
    Route::delete('{id}', [TrainingCourseController::class, 'destroy'])->name('training_course.destroy')->middleware(['can:rh-formacao-delete']);
});

Route::prefix('sessions')->group(function () {
    Route::get('/', [TrainingSessionController::class, 'index'])->name('training_session.index')->middleware(['can:rh-formacao-show']);
    Route::post('/', [TrainingSessionController::class, 'store'])->name('training_session.store')->middleware(['can:rh-formacao-create']);
    Route::get('{id}', [TrainingSessionController::class, 'show'])->name('training_session.show')->middleware(['can:rh-formacao-show']);
    Route::put('{id}', [TrainingSessionController::class, 'update'])->name('training_session.update')->middleware(['can:rh-formacao-edit']);
    Route::delete('{id}', [TrainingSessionController::class, 'destroy'])->name('training_session.destroy')->middleware(['can:rh-formacao-delete']);
});

Route::prefix('enrollments')->group(function () {
    Route::get('/', [TrainingEnrollmentController::class, 'index'])->name('training_enrollment.index')->middleware(['can:rh-formacao-show']);
    Route::post('/', [TrainingEnrollmentController::class, 'store'])->name('training_enrollment.store')->middleware(['can:rh-formacao-create']);
    Route::get('{id}', [TrainingEnrollmentController::class, 'show'])->name('training_enrollment.show')->middleware(['can:rh-formacao-show']);
    Route::put('{id}', [TrainingEnrollmentController::class, 'update'])->name('training_enrollment.update')->middleware(['can:rh-formacao-edit']);
    Route::delete('{id}', [TrainingEnrollmentController::class, 'destroy'])->name('training_enrollment.destroy')->middleware(['can:rh-formacao-delete']);
});

Route::get('{id}', [TrainingCourseController::class, 'show'])->name('training.show')->middleware(['can:rh-formacao-show']);
Route::put('{id}', [TrainingCourseController::class, 'update'])->name('training.update')->middleware(['can:rh-formacao-edit']);
Route::delete('{id}', [TrainingCourseController::class, 'destroy'])->name('training.destroy')->middleware(['can:rh-formacao-delete']);
