<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KYT\KytRuleController;

Route::get('', [KytRuleController::class, 'index'])
    ->name('kyt_rules.index');

Route::post('', [KytRuleController::class, 'store'])
    ->name('kyt_rules.store');

Route::get('{kyt_rule}', [KytRuleController::class, 'show'])
    ->name('kyt_rules.show');

Route::put('{kyt_rule}', [KytRuleController::class, 'update'])
    ->name('kyt_rules.update');

Route::delete('{kyt_rule}', [KytRuleController::class, 'destroy'])
    ->name('kyt_rules.destroy');
