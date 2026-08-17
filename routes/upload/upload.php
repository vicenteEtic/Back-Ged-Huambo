<?php

use App\Http\Controllers\Upload\UploadController;
use Illuminate\Support\Facades\Route;

Route::post('', [UploadController::class, 'store'])->name('upload.store');
Route::delete('{path}', [UploadController::class, 'destroy'])->name('upload.destroy')->where('path', '.*');
Route::get('{path}/info', [UploadController::class, 'info'])->name('upload.info')->where('path', '.*');
