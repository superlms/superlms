<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily clean-up: drop admin announcements older than 60 days (incl. S3 files).
// Supervisor runs `php artisan schedule:run` every 60s, so this fires at 03:15
// server time every day. Adjust the cutoff with --days=N if ever needed.
Schedule::command('announcements:purge-old')
    ->dailyAt('03:15')
    ->withoutOverlapping();

// Every midnight: for any organization + person-type that already had a card
// batch issued, generate ID cards for newly-added students / teachers /
// employees that don't have one yet. See App\Console\Commands\GenerateMissingIdCards.
Schedule::command('id-cards:generate-missing')
    ->dailyAt('00:00')
    ->withoutOverlapping();
