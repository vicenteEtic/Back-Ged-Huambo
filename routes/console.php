<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('rh:check-birthdays')->dailyAt('08:00');
Schedule::command('rh:check-document-expiry --days=30')->dailyAt('06:00');
Schedule::command('rh:check-pending-evaluations')->weeklyOn(1, '09:00'); // Mondays
Schedule::command('rh:check-pending-leaves')->dailyAt('07:00');
