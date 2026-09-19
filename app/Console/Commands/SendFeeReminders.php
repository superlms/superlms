<?php

namespace App\Console\Commands;

use App\Services\StudentPushNotifier;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * The day's fee reminders to students: an academic installment due in 10, 7,
 * 3 or 1 day or today, or a day late (the late fee has started). Scheduled to
 * run daily — see routes/console.php. A reminder goes once per student,
 * installment and day however often this runs.
 */
class SendFeeReminders extends Command
{
    protected $signature = 'fees:remind {--date= : Remind as if today were this date (Y-m-d)}';

    protected $description = 'Push fee reminders to students for installments falling due (10/7/3/1 days, the day) or a day late.';

    public function handle(StudentPushNotifier $push): int
    {
        $today = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
        $sent = $push->sendFeeReminders($today);

        $this->info("Fee reminders for {$today->toDateString()}: {$sent} sent.");

        return self::SUCCESS;
    }
}
