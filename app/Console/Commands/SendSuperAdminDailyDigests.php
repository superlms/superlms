<?php

namespace App\Console\Commands;

use App\Models\Admin\ContactSuperAdmin;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Organization;
use App\Models\SchoolListing;
use App\Models\Student\StudentDetail;
use App\Models\SuperAdmin\CreditQuery;
use App\Models\SuperAdmin\SuperAdminFeePayment;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use App\Models\WebsiteContact;
use App\Models\WebsiteDemo;
use App\Services\ActivityNotifier;
use App\Support\DailyDigestCounters;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * End-of-day (8pm IST) roll-up notifications for super-admins. Each is a
 * separate bell + web-push entry:
 *
 *   1. Listing     — how many schools were added to the directory today.
 *   2. People      — students & teachers added / edited / deleted today.
 *   3. Fees        — student fee payments recorded today (count + total).
 *   4. Report      — the day's platform report across every metric.
 *
 * Scheduled from routes/console.php. Fails open per-section so one bad query
 * never blocks the others.
 */
class SendSuperAdminDailyDigests extends Command
{
    protected $signature = 'superadmin:daily-digests {--date= : Day to summarise (Y-m-d), defaults to today}';

    protected $description = 'Send the super-admins their 8pm listing / people / fees / report digests.';

    public function handle(): int
    {
        $day      = $this->option('date') ? Carbon::parse($this->option('date')) : now();
        $date     = $day->toDateString();
        $dayStart = $day->copy()->startOfDay();
        $label    = $day->format('d M Y');

        $this->listingDigest($date, $label);
        $this->peopleDigest($date, $dayStart, $label);
        $this->feesDigest($date, $label);
        $this->reportDigest($date, $dayStart, $label);

        $this->info("Daily digests sent for {$label}.");

        return self::SUCCESS;
    }

    // ─── 1. Schools added to the listing/directory ────────────────────────────
    private function listingDigest(string $date, string $label): void
    {
        $this->safely(function () use ($date, $label) {
            $count = SchoolListing::whereDate('created_at', $date)->count();

            ActivityNotifier::toSuperAdmins(
                'Daily listing summary',
                $count === 0
                    ? "No new schools were added to the listing today ({$label})."
                    : "{$count} school(s) added to the listing today ({$label}).",
                ['type' => 'digest_listing', 'date' => $date, 'count' => $count]
            );
        });
    }

    // ─── 2. Students & teachers added / edited / deleted ──────────────────────
    private function peopleDigest(string $date, Carbon $dayStart, string $label): void
    {
        $this->safely(function () use ($date, $dayStart, $label) {
            $studentsAdded  = StudentDetail::whereDate('created_at', $date)->count();
            $studentsEdited = StudentDetail::whereDate('updated_at', $date)
                ->where('created_at', '<', $dayStart)->count();
            $studentsDeleted = DailyDigestCounters::pull('students_deleted', $date);

            $teachersAdded  = TeacherDetail::whereDate('created_at', $date)->count();
            $teachersEdited = TeacherDetail::whereDate('updated_at', $date)
                ->where('created_at', '<', $dayStart)->count();
            $teachersDeleted = DailyDigestCounters::pull('teachers_deleted', $date);

            ActivityNotifier::toSuperAdmins(
                'Daily students & teachers summary',
                "Today ({$label}) — "
                    . "Students: {$studentsAdded} added, {$studentsEdited} edited, {$studentsDeleted} deleted. "
                    . "Teachers: {$teachersAdded} added, {$teachersEdited} edited, {$teachersDeleted} deleted.",
                [
                    'type' => 'digest_people', 'date' => $date,
                    'students_added' => $studentsAdded, 'students_edited' => $studentsEdited,
                    'students_deleted' => $studentsDeleted,
                    'teachers_added' => $teachersAdded, 'teachers_edited' => $teachersEdited,
                    'teachers_deleted' => $teachersDeleted,
                ]
            );
        });
    }

    // ─── 3. Student fees updated today ────────────────────────────────────────
    private function feesDigest(string $date, string $label): void
    {
        $this->safely(function () use ($date, $label) {
            $query    = FeePayment::whereDate('payment_date', $date);
            $payments = (clone $query)->count();
            $students = (clone $query)->distinct('student_detail_id')->count('student_detail_id');
            $total    = (float) (clone $query)->sum('amount');

            ActivityNotifier::toSuperAdmins(
                'Daily fees summary',
                $payments === 0
                    ? "No student fees were updated today ({$label})."
                    : "{$students} student(s) had fees updated today ({$label}) across {$payments} payment(s), "
                        . 'totalling ₹' . number_format($total, 2) . '.',
                [
                    'type' => 'digest_fees', 'date' => $date,
                    'students' => $students, 'payments' => $payments, 'total' => $total,
                ]
            );
        });
    }

    // ─── 4. Platform report roll-up for the day ───────────────────────────────
    private function reportDigest(string $date, Carbon $dayStart, string $label): void
    {
        $this->safely(function () use ($date, $label) {
            $students  = User::where('role', 'user')->whereDate('created_at', $date)->count();
            $teachers  = User::where('role', 'teacher')->whereDate('created_at', $date)->count();
            $schools   = Organization::whereDate('created_at', $date)->count();
            $revenue   = (float) SuperAdminFeePayment::paid()->whereDate('created_at', $date)->sum('amount');
            $fees      = (float) FeePayment::whereDate('payment_date', $date)->sum('amount');
            $credit    = CreditQuery::whereDate('created_at', $date)->count();
            $support   = ContactSuperAdmin::whereDate('created_at', $date)->count();
            $enquiries = WebsiteDemo::whereDate('created_at', $date)->count()
                       + WebsiteContact::whereDate('created_at', $date)->count();

            ActivityNotifier::toSuperAdmins(
                'Daily report',
                "Report for {$label} — "
                    . "New students: {$students}, teachers: {$teachers}, schools: {$schools}. "
                    . 'Platform revenue: ₹' . number_format($revenue, 2) . ', '
                    . 'fees collected: ₹' . number_format($fees, 2) . '. '
                    . "Credit applications: {$credit}, support tickets: {$support}, enquiries: {$enquiries}.",
                [
                    'type' => 'digest_report', 'date' => $date,
                    'students' => $students, 'teachers' => $teachers, 'schools' => $schools,
                    'revenue' => $revenue, 'fees' => $fees, 'credit' => $credit,
                    'support' => $support, 'enquiries' => $enquiries,
                ]
            );
        });
    }

    /** Run one digest section; a failure is logged but never aborts the rest. */
    private function safely(\Closure $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            report($e);
            $this->warn('Digest section failed: ' . $e->getMessage());
        }
    }
}
