<?php

namespace App\Console\Commands;

use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppSchoolFeature;
use App\Services\SchoolWhatsApp;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

/**
 * Every few minutes (routes/console.php): WhatsApp the students marked absent
 * today, in the schools that have "Absent alert" on (SchoolWhatsApp) — one
 * message a student a day.
 *
 * Attendance is saved from the teacher app, the panel, the admin app and the
 * accounts desk, partly in bulk without model events, so this reads the day's
 * marks rather than hanging off each save. A mark has to stand for a few
 * minutes first (--settle), so a student marked absent by mistake and put
 * right straight away gets nothing; daytime only (6 am – 8 pm IST).
 */
class SendWhatsAppAbsentAlerts extends Command
{
    protected $signature = 'whatsapp:absent-alerts
        {--date= : A day other than today (for a test run; ignores the hours)}
        {--settle=10 : Minutes an absent mark must stand before it is sent}';

    protected $description = 'WhatsApp the students marked absent today, in schools that have Absent alert on';

    public function handle(): int
    {
        if (!SchoolWhatsApp::tracked() || !WhatsAppService::enabled()) {
            return self::SUCCESS;
        }

        $now  = now();
        $date = $this->option('date') ?: $now->toDateString();
        if (!$this->option('date') && ($now->hour < 6 || $now->hour >= 20)) {
            return self::SUCCESS;
        }
        $settled = $now->copy()->subMinutes(max(0, (int) $this->option('settle')))->toDateTimeString();

        $sent = 0;
        $orgIds = WhatsAppSchoolFeature::where('feature', 'absent_alert')->where('enabled', true)->pluck('organization_id');

        foreach ($orgIds as $orgId) {
            $ids = StudentAttendance::where('organization_id', $orgId)
                ->whereDate('attendance_date', $date)
                ->where('status', 0)
                ->whereRaw('COALESCE(updated_at, created_at) <= ?', [$settled])
                ->pluck('student_detail_id')
                ->unique()
                ->values();
            if ($ids->isEmpty()) {
                continue;
            }

            // Who already had today's message.
            $done = WhatsAppMessage::whereIn('dedupe_key', $ids->map(fn ($id) => "absent_alert:{$id}:{$date}")->all())
                ->pluck('dedupe_key')->flip();

            $students = StudentDetail::with(['user', 'standard', 'section'])
                ->where('organization_id', $orgId)
                ->whereIn('id', $ids->reject(fn ($id) => isset($done["absent_alert:{$id}:{$date}"]))->all())
                ->get();

            foreach ($students as $student) {
                try {
                    if (SchoolWhatsApp::absentAlert($student, $date)) {
                        $sent++;
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        $this->info("Absent alerts sent: {$sent}");

        return self::SUCCESS;
    }
}
