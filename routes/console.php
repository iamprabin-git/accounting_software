<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('subscriptions:deactivate-expired')->dailyAt('01:00');
Schedule::command('audits:verify-integrity --notify')->dailyAt('02:00');

if (config('company_portable_db.schedule_enabled')) {
    Schedule::command('company:write-daily-portable-database --all')->dailyAt('01:15');
}
