<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Check IPOST statuses every 7 minutes and notify on change
Schedule::command('ipost:check-statuses')->cron('*/7 * * * *');
