<?php

namespace App\Support;

use App\Models\Admin\Announcement;
use App\Models\Admin\AdmissionEnquiry;
use App\Models\Admin\ContactAdminStudent;
use App\Models\Admin\ContactAdminTeacher;
use App\Models\Admin\ExamCopy;
use App\Models\Admin\Fee\CustomFeePayment;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\HomeWork;
use App\Models\Admin\ReceptionVisitor;
use App\Models\Admin\SchoolComplaint;
use App\Models\Admin\StudentGatePass;
use App\Models\Admin\TransportFeePayment;
use App\Models\SchoolWebsiteEnquiry;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use App\Services\GradingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * The admin Analytics page's extra detail, tab by tab (user's ask of
 * 2026-11-25: "every tab in as much detail as you can"). Worked out on each
 * render and kept a minute per school and filter, so the page's own state
 * stays small and a page that reloads itself every few seconds does not ask
 * the database again each time. Every figure is read on its own guard: a
 * table a school does not have leaves that one figure empty, never the page.
 *
 * Attendance statuses: 1 present, 0 absent, 2 half day, 3 / 4 holiday.
 */
class AdminAnalyticsDetail
{
    private const BOYS  = ['male', 'm', 'boy', 'boys'];
    private const GIRLS = ['female', 'f', 'girl', 'girls'];

    /** @return array<string, mixed> */
    public static function for(int $orgId, string $class = '', string $section = '', int $admissionYear = 0): array
    {
        $key = "admin-analytics-detail:{$orgId}:{$class}:{$section}:{$admissionYear}:" . now()->toDateString();

        return Cache::remember($key, 60, function () use ($orgId, $class, $section, $admissionYear) {
            $safe = fn (callable $read, $empty = []) => rescue($read, $empty, false);
            $classes = $safe(fn () => Standard::where('organization_id', $orgId)->inClassOrder()->get(['id', 'name'])
                ->mapWithKeys(fn ($s) => [(int) $s->id => (string) $s->name])->all());

            return [
                // Key Metrics
                'glance'     => $safe(fn () => self::glance($orgId, $classes)),
                'byClass'    => $safe(fn () => self::studentsByClass($orgId, $classes)),
                'months'     => $safe(fn () => self::monthAgainstMonth($orgId)),
                // Attendance
                'todayClass' => $safe(fn () => self::todayByClass($orgId, $classes)),
                'absentees'  => $safe(fn () => self::mostAbsentStudents($orgId)),
                'teacherAbs' => $safe(fn () => self::mostAbsentTeachers($orgId)),
                'weekdays'   => $safe(fn () => self::weekdayPattern($orgId)),
                // Performance
                'exams'      => $safe(fn () => self::examResults($orgId, $class, $section)),
                'subjects'   => $safe(fn () => self::subjectAverages($orgId, $class, $section)),
                'bands'      => $safe(fn () => self::scoreBands($orgId, $class, $section)),
                'classAvg'   => $safe(fn () => self::classAverages($orgId, $classes)),
                // Admissions & enquiries
                'admitClass' => $safe(fn () => self::admissionsByClass($orgId, $classes, $admissionYear)),
                'apps'       => $safe(fn () => self::applications($orgId, $classes)),
                'enqMonths'  => $safe(fn () => self::enquiriesByMonth($orgId)),
                // Fee
                'feeWindows' => $safe(fn () => self::feeWindows($orgId)),
                'modes'      => $safe(fn () => self::paymentModes($orgId)),
                'recentPay'  => $safe(fn () => self::recentPayments($orgId)),
                // Operations
                'desk'       => $safe(fn () => self::frontDesk($orgId)),
                'homework'   => $safe(fn () => self::homeworkByClass($orgId, $classes)),
                'complaints' => $safe(fn () => self::latestComplaints($orgId)),
            ];
        });
    }

    // ─── Key Metrics ─────────────────────────────────────────────────────────

    private static function glance(int $orgId, array $classes): array
    {
        $genders = self::genderCounts(StudentDetail::where('organization_id', $orgId));

        return [
            'students' => $genders['total'],
            'boys'     => $genders['boys'],
            'girls'    => $genders['girls'],
            'teachers' => TeacherDetail::where('organization_id', $orgId)->count(),
            'classes'  => count($classes),
            'sections' => $classes ? Section::whereIn('standard_id', array_keys($classes))->count() : 0,
            'subjects' => Subject::where('organization_id', $orgId)->count(),
            'riders'   => StudentDetail::where('organization_id', $orgId)->where('transportation_required', 1)->count(),
        ];
    }

    /** Each class: its sections, students, boys and girls, and how many joined in the last 30 days. */
    private static function studentsByClass(int $orgId, array $classes): array
    {
        if (!$classes) {
            return [];
        }
        $rows = StudentDetail::where('organization_id', $orgId)->whereIn('standard_id', array_keys($classes))
            ->selectRaw("standard_id, LOWER(COALESCE(gender, '')) as g, COUNT(*) as n")
            ->groupBy('standard_id', 'g')->toBase()->get();
        $new = StudentDetail::where('organization_id', $orgId)->whereIn('standard_id', array_keys($classes))
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('standard_id, COUNT(*) as n')->groupBy('standard_id')->pluck('n', 'standard_id');
        $sections = Section::whereIn('standard_id', array_keys($classes))
            ->selectRaw('standard_id, COUNT(*) as n')->groupBy('standard_id')->pluck('n', 'standard_id');

        $out = [];
        foreach ($classes as $id => $name) {
            $out[$id] = ['name' => $name, 'sections' => (int) ($sections[$id] ?? 0), 'students' => 0, 'boys' => 0, 'girls' => 0, 'new' => (int) ($new[$id] ?? 0)];
        }
        foreach ($rows as $r) {
            $id = (int) $r->standard_id;
            $out[$id]['students'] += (int) $r->n;
            if (in_array($r->g, self::BOYS, true)) $out[$id]['boys'] += (int) $r->n;
            if (in_array($r->g, self::GIRLS, true)) $out[$id]['girls'] += (int) $r->n;
        }

        return array_values($out);
    }

    /** This month against last month, figure by figure. */
    private static function monthAgainstMonth(int $orgId): array
    {
        $thisStart = now()->startOfMonth();
        $lastStart = $thisStart->copy()->subMonthNoOverflow();
        $lastEnd   = $thisStart->copy()->subDay()->endOfDay();
        $split = function ($query, string $column) use ($thisStart, $lastStart, $lastEnd) {
            return [
                (clone $query)->where($column, '>=', $thisStart)->count(),
                (clone $query)->whereBetween($column, [$lastStart, $lastEnd])->count(),
            ];
        };
        $guard = fn (callable $read) => rescue($read, [0, 0], false);

        $fee = SchoolMonthly::feeByDay($orgId, $lastStart->toDateString());
        $feeThis = $feeLast = 0.0;
        foreach ($fee as $day => $amount) {
            if ($day >= $thisStart->toDateString()) $feeThis += $amount; else $feeLast += $amount;
        }

        $rate = function (string $model, $from, $to, bool $student) use ($orgId) {
            $q = $model::where('organization_id', $orgId)->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()]);
            $present = (clone $q)->where('status', 1)->count();
            $absent  = (clone $q)->where('status', 0)->count();
            $half    = $student ? (clone $q)->where('status', 2)->count() : 0;
            $all     = $present + $absent + $half;

            return $all > 0 ? round(($present + 0.5 * $half) / $all * 100, 1) : null;
        };

        $enquiries = function () use ($orgId, $split) {
            $sum = [0, 0];
            foreach ([ContactAdminStudent::class, ContactAdminTeacher::class, SchoolWebsiteEnquiry::class] as $model) {
                [$a, $b] = rescue(fn () => $split($model::where('organization_id', $orgId), 'created_at'), [0, 0], false);
                $sum = [$sum[0] + $a, $sum[1] + $b];
            }
            return $sum;
        };

        return [
            ['Fee collected', round($feeThis), round($feeLast), 'money'],
            ['New students', ...$guard(fn () => $split(StudentDetail::where('organization_id', $orgId), 'created_at')), 'count'],
            ['Student attendance', rescue(fn () => $rate(StudentAttendance::class, $thisStart, now(), true), null, false),
                rescue(fn () => $rate(StudentAttendance::class, $lastStart, $lastEnd, true), null, false), 'pct'],
            ['Teacher attendance', rescue(fn () => $rate(TeacherAttendance::class, $thisStart, now(), false), null, false),
                rescue(fn () => $rate(TeacherAttendance::class, $lastStart, $lastEnd, false), null, false), 'pct'],
            ['Queries & enquiries', ...$enquiries(), 'count'],
            ['Admission applications', ...$guard(fn () => $split(AdmissionEnquiry::where('organization_id', $orgId), 'created_at')), 'count'],
            ['Homework given', ...$guard(fn () => $split(HomeWork::where('organization_id', $orgId), 'created_at')), 'count'],
            ['Announcements', ...$guard(fn () => $split(Announcement::where('organization_id', $orgId), 'created_at')), 'count'],
            ['Gate passes', ...$guard(fn () => $split(StudentGatePass::where('organization_id', $orgId), 'out_at')), 'count'],
            ['Visitors', ...$guard(fn () => $split(ReceptionVisitor::where('organization_id', $orgId), 'in_at')), 'count'],
            ['Complaints', ...$guard(fn () => $split(SchoolComplaint::where('organization_id', $orgId), 'created_at')), 'count'],
        ];
    }

    // ─── Attendance ──────────────────────────────────────────────────────────

    /** Today, class by class: students, present, half day, absent, holiday, not marked, and the %. */
    private static function todayByClass(int $orgId, array $classes): array
    {
        if (!$classes) {
            return [];
        }
        $totals = StudentDetail::where('organization_id', $orgId)->whereIn('standard_id', array_keys($classes))
            ->selectRaw('standard_id, COUNT(*) as n')->groupBy('standard_id')->pluck('n', 'standard_id');
        $marks = StudentAttendance::join('student_details as sd', 'sd.id', '=', 'student_attendances.student_detail_id')
            ->where('student_attendances.organization_id', $orgId)
            ->whereDate('student_attendances.attendance_date', today())
            ->selectRaw('sd.standard_id as c, student_attendances.status as s, COUNT(*) as n')
            ->groupBy('sd.standard_id', 'student_attendances.status')->toBase()->get();

        $out = [];
        foreach ($classes as $id => $name) {
            $out[$id] = ['name' => $name, 'students' => (int) ($totals[$id] ?? 0), 'present' => 0, 'half' => 0, 'absent' => 0, 'holiday' => 0];
        }
        foreach ($marks as $m) {
            $id = (int) $m->c;
            if (!isset($out[$id])) continue;
            $key = match ((int) $m->s) { 1 => 'present', 2 => 'half', 0 => 'absent', default => 'holiday' };
            $out[$id][$key] += (int) $m->n;
        }
        foreach ($out as &$row) {
            $marked = $row['present'] + $row['half'] + $row['absent'] + $row['holiday'];
            $row['unmarked'] = max(0, $row['students'] - $marked);
            $worked = $row['present'] + $row['half'] + $row['absent'];
            $row['pct'] = $worked > 0 ? round(($row['present'] + 0.5 * $row['half']) / $worked * 100, 1) : null;
        }
        unset($row);

        return array_values(array_filter($out, fn ($r) => $r['students'] > 0));
    }

    /** The ten students absent most in the last 30 days. */
    private static function mostAbsentStudents(int $orgId): array
    {
        $rows = StudentAttendance::where('organization_id', $orgId)
            ->where('attendance_date', '>=', now()->subDays(29)->toDateString())
            ->whereNotNull('student_detail_id')
            ->selectRaw('student_detail_id as id,'
                . ' SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as absent,'
                . ' SUM(CASE WHEN status IN (1, 2) THEN 1 ELSE 0 END) as present')
            ->groupBy('student_detail_id')
            ->havingRaw('SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) > 0')
            ->orderByRaw('SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) DESC')
            ->limit(10)->toBase()->get();
        $students = StudentDetail::with(['standard:id,name', 'section:id,name'])
            ->whereIn('id', $rows->pluck('id')->all())->get(['id', 'full_name', 'standard_id', 'section_id'])->keyBy('id');

        return $rows->map(function ($r) use ($students) {
            $s = $students[$r->id] ?? null;
            $days = (int) $r->absent + (int) $r->present;

            return [
                'name'    => $s?->full_name ?: 'Student',
                'class'   => trim(($s?->standard?->name ?? '') . ($s?->section?->name ? ' · ' . $s->section->name : '')) ?: '—',
                'absent'  => (int) $r->absent,
                'present' => (int) $r->present,
                'pct'     => $days > 0 ? round((int) $r->present / $days * 100) : 0,
            ];
        })->values()->all();
    }

    /** The ten teachers absent most in the last 30 days. */
    private static function mostAbsentTeachers(int $orgId): array
    {
        $rows = TeacherAttendance::where('organization_id', $orgId)
            ->where('attendance_date', '>=', now()->subDays(29)->toDateString())
            ->selectRaw('teacher_detail_id as id,'
                . ' SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as absent,'
                . ' SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as present')
            ->groupBy('teacher_detail_id')
            ->havingRaw('SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) > 0')
            ->orderByRaw('SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) DESC')
            ->limit(10)->toBase()->get();
        $teachers = TeacherDetail::with('user:id,name')->whereIn('id', $rows->pluck('id')->all())->get()->keyBy('id');

        return $rows->map(function ($r) use ($teachers) {
            $days = (int) $r->absent + (int) $r->present;

            return [
                'name'    => $teachers[$r->id]?->user?->name ?? 'Teacher',
                'absent'  => (int) $r->absent,
                'present' => (int) $r->present,
                'pct'     => $days > 0 ? round((int) $r->present / $days * 100) : 0,
            ];
        })->values()->all();
    }

    /** Students' attendance by weekday over the last eight weeks: each weekday's days averaged. */
    private static function weekdayPattern(int $orgId): array
    {
        $rows = StudentAttendance::where('organization_id', $orgId)
            ->where('attendance_date', '>=', now()->subWeeks(8)->toDateString())
            ->selectRaw('DATE(attendance_date) as d,'
                . ' SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as p,'
                . ' SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as h,'
                . ' SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as a')
            ->groupBy('d')->toBase()->get();

        $days = [];
        foreach ($rows as $r) {
            $worked = (int) $r->p + (int) $r->h + (int) $r->a;
            if ($worked === 0 || !$r->d) continue;
            $dow = Carbon::parse($r->d)->dayOfWeekIso;   // 1 Mon … 7 Sun
            $days[$dow][] = ((int) $r->p + 0.5 * (int) $r->h) / $worked * 100;
        }

        $out = [];
        foreach ([1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'] as $dow => $label) {
            if (empty($days[$dow]) && $dow === 7) continue;
            $out[] = ['day' => $label, 'pct' => empty($days[$dow]) ? null : round(array_sum($days[$dow]) / count($days[$dow]), 1), 'days' => count($days[$dow] ?? [])];
        }

        return $out;
    }

    // ─── Performance ─────────────────────────────────────────────────────────

    private static function scoped($query, string $class, string $section)
    {
        return $query->join('student_details as sd', 'sd.id', '=', 'exam_copies.student_detail_id')
            ->when($class !== '', fn ($q) => $q->where('sd.standard_id', (int) $class))
            ->when($section !== '', fn ($q) => $q->where('sd.section_id', (int) $section));
    }

    /** The last ten exams with marks: students, average, pass rate, highest and lowest, papers missed. */
    private static function examResults(int $orgId, string $class, string $section): array
    {
        $pass = (float) app(GradingService::class)->passPercentage();
        $per = self::scoped(ExamCopy::query(), $class, $section)
            ->where('exam_copies.organization_id', $orgId)
            ->selectRaw('exam_copies.exam_id as exam, exam_copies.student_detail_id as sid,'
                . ' SUM(CASE WHEN exam_copies.is_absent = 1 THEN 0 ELSE COALESCE(exam_copies.marks_obtained, 0) END) as got,'
                . ' SUM(COALESCE(exam_copies.max_marks, 0)) as outof,'
                . ' SUM(CASE WHEN exam_copies.is_absent = 1 THEN 1 ELSE 0 END) as missed')
            ->groupBy('exam_copies.exam_id', 'exam_copies.student_detail_id')
            ->toBase()->get()->groupBy('exam');
        if ($per->isEmpty()) {
            return ['pass' => $pass, 'rows' => []];
        }
        $exams = \App\Models\Admin\Exam::whereIn('id', $per->keys()->all())
            ->get(['id', 'exam_name', 'start_date', 'end_date'])
            ->sortByDesc(fn ($e) => [($e->end_date ?? $e->start_date)?->toDateString() ?? '', $e->id])
            ->take(10);

        $rows = $exams->map(function ($e) use ($per, $pass) {
            $scores = collect($per[$e->id] ?? [])->filter(fn ($s) => (float) $s->outof > 0)
                ->map(fn ($s) => (float) $s->got / (float) $s->outof * 100);
            $n = $scores->count();
            $when = $e->end_date ?? $e->start_date;

            return [
                'exam'    => $e->exam_name ?: 'Exam #' . $e->id,
                'date'    => $when ? $when->format('d M Y') : '—',
                'students'=> $n,
                'avg'     => $n ? round($scores->avg(), 1) : 0,
                'passPct' => $n ? round($scores->filter(fn ($p) => $p >= $pass)->count() / $n * 100, 1) : 0,
                'high'    => $n ? round($scores->max(), 1) : 0,
                'low'     => $n ? round($scores->min(), 1) : 0,
                'missed'  => (int) collect($per[$e->id] ?? [])->sum('missed'),
            ];
        })->values()->all();

        return ['pass' => $pass, 'rows' => $rows];
    }

    /** Each subject over every exam: papers marked, the average, the best and the weakest paper, papers missed. */
    private static function subjectAverages(int $orgId, string $class, string $section): array
    {
        $rows = self::scoped(ExamCopy::query(), $class, $section)
            ->where('exam_copies.organization_id', $orgId)
            ->whereNotNull('exam_copies.subject_id')
            ->selectRaw('exam_copies.subject_id as subject, COUNT(*) as papers,'
                . ' SUM(CASE WHEN exam_copies.is_absent = 1 THEN 1 ELSE 0 END) as missed,'
                . ' SUM(CASE WHEN exam_copies.is_absent = 1 THEN 0 ELSE COALESCE(exam_copies.marks_obtained, 0) END) as got,'
                . ' SUM(CASE WHEN exam_copies.is_absent = 1 THEN 0 ELSE COALESCE(exam_copies.max_marks, 0) END) as outof,'
                . ' MAX(CASE WHEN exam_copies.is_absent = 1 OR COALESCE(exam_copies.max_marks, 0) = 0 THEN NULL ELSE exam_copies.marks_obtained * 100.0 / exam_copies.max_marks END) as high,'
                . ' MIN(CASE WHEN exam_copies.is_absent = 1 OR COALESCE(exam_copies.max_marks, 0) = 0 THEN NULL ELSE exam_copies.marks_obtained * 100.0 / exam_copies.max_marks END) as low')
            ->groupBy('exam_copies.subject_id')->toBase()->get();
        $names = Subject::whereIn('id', $rows->pluck('subject')->all())->pluck('name', 'id');

        return $rows->map(fn ($r) => [
            'subject' => $names[$r->subject] ?? 'Subject',
            'papers'  => (int) $r->papers,
            'missed'  => (int) $r->missed,
            'avg'     => (float) $r->outof > 0 ? round((float) $r->got / (float) $r->outof * 100, 1) : 0,
            'high'    => $r->high !== null ? round((float) $r->high, 1) : null,
            'low'     => $r->low !== null ? round((float) $r->low, 1) : null,
        ])->sortByDesc('avg')->values()->all();
    }

    /** How the students' overall marks spread, in bands. */
    private static function scoreBands(int $orgId, string $class, string $section): array
    {
        $scores = self::scoped(ExamCopy::query(), $class, $section)
            ->where('exam_copies.organization_id', $orgId)
            ->selectRaw('exam_copies.student_detail_id as sid, SUM(exam_copies.marks_obtained) as got, SUM(exam_copies.max_marks) as outof')
            ->groupBy('exam_copies.student_detail_id')
            ->havingRaw('SUM(exam_copies.max_marks) > 0')
            ->toBase()->get()->map(fn ($r) => (float) $r->got / (float) $r->outof * 100);

        $bands = [['90% and above', 90, 101], ['75 – 90%', 75, 90], ['60 – 75%', 60, 75], ['45 – 60%', 45, 60], ['33 – 45%', 33, 45], ['Below 33%', -1, 33]];
        $total = $scores->count();

        return array_map(function ($b) use ($scores, $total) {
            $n = $scores->filter(fn ($p) => $p >= $b[1] && $p < $b[2])->count();
            return ['label' => $b[0], 'n' => $n, 'pct' => $total ? round($n / $total * 100, 1) : 0];
        }, $bands);
    }

    /** Each class's average over every exam paper. */
    private static function classAverages(int $orgId, array $classes): array
    {
        $rows = ExamCopy::join('student_details as sd', 'sd.id', '=', 'exam_copies.student_detail_id')
            ->where('exam_copies.organization_id', $orgId)
            ->selectRaw('sd.standard_id as c, COUNT(DISTINCT exam_copies.student_detail_id) as students,'
                . ' SUM(CASE WHEN exam_copies.is_absent = 1 THEN 0 ELSE COALESCE(exam_copies.marks_obtained, 0) END) as got,'
                . ' SUM(COALESCE(exam_copies.max_marks, 0)) as outof')
            ->groupBy('sd.standard_id')->toBase()->get()->keyBy('c');

        $out = [];
        foreach ($classes as $id => $name) {
            $r = $rows[$id] ?? null;
            if (!$r || (float) $r->outof <= 0) continue;
            $out[] = ['name' => $name, 'students' => (int) $r->students, 'avg' => round((float) $r->got / (float) $r->outof * 100, 1)];
        }

        return $out;
    }

    // ─── Admissions & enquiries ──────────────────────────────────────────────

    /** The school year's admissions, class by class, with boys and girls. */
    private static function admissionsByClass(int $orgId, array $classes, int $year): array
    {
        if (!$classes || $year <= 0) {
            return [];
        }
        $from = Carbon::create($year, 4, 1)->toDateString();
        $to   = Carbon::create($year + 1, 3, 31)->toDateString();
        $rows = StudentDetail::where('organization_id', $orgId)
            ->whereRaw('DATE(COALESCE(date_of_admission, created_at)) BETWEEN ? AND ?', [$from, $to])
            ->selectRaw("standard_id, LOWER(COALESCE(gender, '')) as g, COUNT(*) as n")
            ->groupBy('standard_id', 'g')->toBase()->get();

        $out = [];
        foreach ($rows as $r) {
            $id = (int) $r->standard_id;
            $out[$id] ??= ['name' => $classes[$id] ?? 'No class', 'n' => 0, 'boys' => 0, 'girls' => 0];
            $out[$id]['n'] += (int) $r->n;
            if (in_array($r->g, self::BOYS, true)) $out[$id]['boys'] += (int) $r->n;
            if (in_array($r->g, self::GIRLS, true)) $out[$id]['girls'] += (int) $r->n;
        }
        // In the school's class order.
        $order = array_flip(array_keys($classes));
        uksort($out, fn ($a, $b) => ($order[$a] ?? PHP_INT_MAX) <=> ($order[$b] ?? PHP_INT_MAX));

        return array_values($out);
    }

    /** Admission applications (More → Admissions): how many, by status, by class applied for, fee taken. */
    private static function applications(int $orgId, array $classes): array
    {
        $q = AdmissionEnquiry::where('organization_id', $orgId);
        $byStatus = (clone $q)->selectRaw("LOWER(COALESCE(status, 'pending')) as s, COUNT(*) as n")->groupBy('s')->pluck('n', 's')->all();
        $byClass  = (clone $q)->whereNotNull('standard_id')->selectRaw('standard_id, COUNT(*) as n')
            ->groupBy('standard_id')->orderByDesc('n')->limit(8)->pluck('n', 'standard_id');

        return [
            'total'     => array_sum($byStatus),
            'thisMonth' => (clone $q)->where('created_at', '>=', now()->startOfMonth())->count(),
            'website'   => (clone $q)->where('source', 'website')->count(),
            'collected' => (float) (clone $q)->sum('collected_amount'),
            'status'    => collect($byStatus)->map(fn ($n, $s) => ['label' => ucfirst($s), 'n' => (int) $n])->sortByDesc('n')->values()->all(),
            'classes'   => $byClass->map(fn ($n, $id) => ['name' => $classes[(int) $id] ?? 'Class', 'n' => (int) $n])->values()->all(),
        ];
    }

    /** Queries and enquiries over the last six months, by where they came from. */
    private static function enquiriesByMonth(int $orgId): array
    {
        $start = now()->startOfMonth()->subMonthsNoOverflow(5);
        $months = [];
        for ($i = 0; $i < 6; $i++) {
            $m = $start->copy()->addMonthsNoOverflow($i);
            $months[$m->format('Y-m')] = ['label' => $m->format('M Y'), 'student' => 0, 'teacher' => 0, 'website' => 0, 'admission' => 0];
        }
        foreach (['student' => ContactAdminStudent::class, 'teacher' => ContactAdminTeacher::class,
                  'website' => SchoolWebsiteEnquiry::class, 'admission' => AdmissionEnquiry::class] as $key => $model) {
            rescue(function () use ($model, $orgId, $start, $key, &$months) {
                $model::where('organization_id', $orgId)->where('created_at', '>=', $start)->get(['created_at'])
                    ->each(function ($r) use ($key, &$months) {
                        $ym = Carbon::parse($r->created_at)->format('Y-m');
                        if (isset($months[$ym])) $months[$ym][$key]++;
                    });
            }, null, false);
        }

        return array_values(array_reverse($months));
    }

    // ─── Fee ─────────────────────────────────────────────────────────────────

    /** What came in today, yesterday, this week, this month, last month and this session. */
    private static function feeWindows(int $orgId): array
    {
        $today   = now()->toDateString();
        $yest    = now()->subDay()->toDateString();
        $week    = now()->startOfWeek()->toDateString();
        $month   = now()->startOfMonth()->toDateString();
        $lastM   = now()->startOfMonth()->subMonthNoOverflow()->toDateString();
        $session = AcademicYear::start()->toDateString();
        $from    = min($session, $lastM, $week);

        $byDay = SchoolMonthly::feeByDay($orgId, $from);
        $sum = fn (string $a, string $b) => round(array_sum(array_filter($byDay, fn ($v, $d) => $d >= $a && $d <= $b, ARRAY_FILTER_USE_BOTH)));
        $lastEnd = now()->startOfMonth()->subDay()->toDateString();

        return [
            ['Today', $sum($today, $today)],
            ['Yesterday', $sum($yest, $yest)],
            ['This week', $sum($week, $today)],
            ['This month', $sum($month, $today)],
            ['Last month', $sum($lastM, $lastEnd)],
            ['This session', $sum($session, $today)],
        ];
    }

    /** How the session's fee was paid: each mode's payments and amount. */
    private static function paymentModes(int $orgId): array
    {
        $from = AcademicYear::start()->toDateString();
        $models = CustomFee::enabled($orgId) ? [CustomFeePayment::class] : [FeePayment::class, TransportFeePayment::class];
        $modes = [];
        foreach ($models as $model) {
            rescue(function () use (&$modes, $model, $orgId, $from) {
                $model::where('organization_id', $orgId)->where('payment_date', '>=', $from)
                ->selectRaw("LOWER(COALESCE(NULLIF(TRIM(payment_mode), ''), 'not given')) as m, COUNT(*) as n, SUM(amount) as a")
                ->groupBy('m')->toBase()->get()
                ->each(function ($r) use (&$modes) {
                    $modes[$r->m] ??= ['n' => 0, 'a' => 0.0];
                    $modes[$r->m]['n'] += (int) $r->n;
                    $modes[$r->m]['a'] += (float) $r->a;
                });
            }, null, false);
        }
        $total = array_sum(array_column($modes, 'a'));

        return collect($modes)->map(fn ($v, $m) => [
            'mode'   => self::modeName($m),
            'n'      => $v['n'],
            'amount' => round($v['a']),
            'share'  => $total > 0 ? round($v['a'] / $total * 100, 1) : 0,
        ])->sortByDesc('amount')->values()->all();
    }

    /** The ten latest payments. */
    private static function recentPayments(int $orgId): array
    {
        $models = CustomFee::enabled($orgId)
            ? [['Fee', CustomFeePayment::class]]
            : [['Academic', FeePayment::class], ['Transport', TransportFeePayment::class]];
        $rows = collect();
        foreach ($models as [$kind, $model]) {
            $rows = $rows->merge(rescue(fn () => $model::where('organization_id', $orgId)
                ->with(['studentDetail:id,full_name,standard_id,section_id', 'studentDetail.standard:id,name', 'studentDetail.section:id,name'])
                ->orderByDesc('payment_date')->orderByDesc('id')->limit(10)->get()
                ->map(fn ($p) => [
                    'kind'    => $kind === 'Academic' && ($p->fee_type ?? '') === 'transport' ? 'Transport' : $kind,
                    'name'    => $p->studentDetail?->full_name ?: 'Student',
                    'class'   => trim(($p->studentDetail?->standard?->name ?? '') . ($p->studentDetail?->section?->name ? ' · ' . $p->studentDetail->section->name : '')) ?: '—',
                    'amount'  => (float) $p->amount,
                    'mode'    => $p->payment_mode ? self::modeName($p->payment_mode) : '—',
                    'date'    => $p->payment_date ? Carbon::parse($p->payment_date) : null,
                    'receipt' => $p->receipt_number ?? null,
                    'sort'    => ($p->payment_date ? Carbon::parse($p->payment_date)->toDateString() : '') . sprintf('%012d', $p->id),
                ]), collect(), false));
        }

        return $rows->sortByDesc('sort')->take(10)->map(function ($r) {
            $r['date'] = $r['date']?->format('d M Y') ?? '—';
            unset($r['sort']);
            return $r;
        })->values()->all();
    }

    // ─── Operations ──────────────────────────────────────────────────────────

    /** The front desk and the day: visitors, gate passes, complaints, homework, announcements. */
    private static function frontDesk(int $orgId): array
    {
        $today = today();
        $count = fn (callable $read) => (int) rescue($read, 0, false);

        return [
            ['Visitors today', $count(fn () => ReceptionVisitor::where('organization_id', $orgId)->whereDate('in_at', $today)->count())],
            ['Visitors still in', $count(fn () => ReceptionVisitor::where('organization_id', $orgId)->whereDate('in_at', $today)->whereNull('out_at')->count())],
            ['Gate passes today', $count(fn () => StudentGatePass::where('organization_id', $orgId)->whereDate('out_at', $today)->count())],
            ['Not back yet', $count(fn () => StudentGatePass::where('organization_id', $orgId)->whereDate('out_at', $today)->whereNull('returned_at')->count())],
            ['Complaints this month', $count(fn () => SchoolComplaint::where('organization_id', $orgId)->where('created_at', '>=', now()->startOfMonth())->count())],
            ['Homework today', $count(fn () => HomeWork::where('organization_id', $orgId)->whereDate('created_at', $today)->count())],
            ['Homework this week', $count(fn () => HomeWork::where('organization_id', $orgId)->where('created_at', '>=', now()->startOfWeek())->count())],
            ['Announcements this month', $count(fn () => Announcement::where('organization_id', $orgId)->where('created_at', '>=', now()->startOfMonth())->count())],
        ];
    }

    /** Homework given in the last seven days, class by class, with its subjects and the last day given. */
    private static function homeworkByClass(int $orgId, array $classes): array
    {
        $rows = HomeWork::where('organization_id', $orgId)->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('standard_id, COUNT(*) as n, COUNT(DISTINCT subject_id) as subjects, MAX(created_at) as last')
            ->groupBy('standard_id')->toBase()->get()->keyBy('standard_id');

        $out = [];
        foreach ($classes as $id => $name) {
            $r = $rows[$id] ?? null;
            $out[] = ['name' => $name, 'n' => (int) ($r->n ?? 0), 'subjects' => (int) ($r->subjects ?? 0),
                'last' => $r && $r->last ? Carbon::parse($r->last)->format('d M') : '—'];
        }

        return $out;
    }

    /** The five latest complaints about students. */
    private static function latestComplaints(int $orgId): array
    {
        return SchoolComplaint::where('organization_id', $orgId)
            ->with(['studentDetail:id,full_name,standard_id,section_id', 'studentDetail.standard:id,name', 'studentDetail.section:id,name'])
            ->latest()->limit(5)->get()
            ->map(fn ($c) => [
                'title' => $c->title ?: 'Complaint',
                'name'  => $c->studentDetail?->full_name ?: 'Student',
                'class' => trim(($c->studentDetail?->standard?->name ?? '') . ($c->studentDetail?->section?->name ? ' · ' . $c->studentDetail->section->name : '')) ?: '—',
                'date'  => $c->created_at?->format('d M Y') ?? '—',
            ])->all();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /** "cash" → "Cash", "upi" → "UPI". */
    private static function modeName(string $mode): string
    {
        $mode = strtolower(trim($mode));

        return in_array($mode, ['upi', 'neft', 'rtgs', 'imps', 'qr', 'dd', 'pos'], true) ? strtoupper($mode) : ucwords($mode);
    }

    /** @return array{total:int, boys:int, girls:int} */
    private static function genderCounts($query): array
    {
        $rows = $query->selectRaw("LOWER(COALESCE(gender, '')) as g, COUNT(*) as n")->groupBy('g')->pluck('n', 'g');
        $out = ['total' => 0, 'boys' => 0, 'girls' => 0];
        foreach ($rows as $g => $n) {
            $out['total'] += (int) $n;
            if (in_array($g, self::BOYS, true)) $out['boys'] += (int) $n;
            if (in_array($g, self::GIRLS, true)) $out['girls'] += (int) $n;
        }

        return $out;
    }
}
