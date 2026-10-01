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

// Daily clean-up: permanently delete homework older than 30 days (incl. S3
// files) — homework added today is gone 30 days later. Adjust with --days=N.
Schedule::command('homework:purge-old')
    ->dailyAt('03:20')
    ->withoutOverlapping();

// Every midnight (IST): in each school that issues ID cards, give a card to the
// students, teachers and employees added during the day — anyone without one.
// See App\Console\Commands\GenerateMissingIdCards.
//
// It runs again every half hour until 5:30 am. The command only fills gaps, so
// a second run costs nothing — but a single run at 00:00 was lost whenever that
// one minute was missed (the scheduler restarts with every deploy), and its
// day-long overlap lock, left behind by a run that was cut short, then kept the
// next midnight's run out as well. The lock now lasts 20 minutes.
Schedule::command('id-cards:generate-missing')
    ->cron('0,30 0-5 * * *')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping(20);

// Every morning at 9am IST: fee reminders to students — an academic
// installment due in 10, 7, 3 or 1 day or today, or a day late (the late fee
// has started). See App\Services\StudentPushNotifier::sendFeeReminders().
Schedule::command('fees:remind')
    ->dailyAt('09:00')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping();

// Every evening at 8pm IST: send super-admins their end-of-day roll-up
// notifications (schools added to the listing, students/teachers added-edited-
// deleted, student fees updated, and the day's platform report). Each lands in
// the header bell + web push. See App\Console\Commands\SendSuperAdminDailyDigests.
Schedule::command('superadmin:daily-digests')
    ->dailyAt('20:00')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping();
