<?php

namespace App\Http\Controllers\v1;

use App\Livewire\Admin\Analytics;
use App\Livewire\Admin\Home;
use App\Models\Admin\TeacherArrangement;
use App\Models\Teacher\TeacherDetail;
use App\Services\SchoolWhatsApp;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * The web panel's Dashboard (Livewire\Admin\Home) and Analytics
 * (Livewire\Admin\Analytics) for the admin app. The same components are
 * mounted as the panel mounts them, so every figure, list and graph the app
 * draws is the panel's own, and the app lays them out in the panel's order.
 * What the panel's views work out themselves (the attendance and collection
 * percentages, the WhatsApp tiles) is worked out here the same way.
 *
 *   GET  /api/v1/admin/panel/home
 *   GET  /api/v1/admin/panel/analytics   class, section (Student Performance),
 *        admission_year (Admissions Trend), wa_period, wa_feature, wa_status,
 *        wa_shown (WhatsApp)
 *   POST /api/v1/admin/panel/arrangement/{id}   teacher_id — Operations' Assign
 */
class AdminPanelController extends ApiController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

    // ── Dashboard ──────────────────────────────────────────────────────────────
    public function home()
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return $err;

        $h = new Home();
        $h->mount();

        // As home.blade.php works them out.
        $studentAttPct = $h->totalStudents > 0 ? round(($h->studentsPresentToday / $h->totalStudents) * 100, 1) : 0;
        $teacherAttPct = $h->totalTeachers > 0 ? round(($h->teachersPresentToday / $h->totalTeachers) * 100, 1) : 0;
        $collectionPct = $h->totalFee > 0 ? round(($h->overallFeeCollected / $h->totalFee) * 100, 1) : 0;

        $pick = $h->trendDay();
        if ($pick) {
            $pick['date_label'] = Carbon::parse($pick['date'])->format('D, d M Y');
        }

        $exams  = array_values($h->examTrend);
        $latest = $exams ? $exams[count($exams) - 1] : null;

        $rating = $h->latestRating;

        return $this->success([
            'date' => now()->format('l, d M Y'),

            'overview' => [
                'students'        => (int) $h->totalStudents,
                'active_students' => (int) $h->activeStudents,
                'teachers'        => (int) $h->totalTeachers,
                'active_teachers' => (int) $h->activeTeachers,
                'classes'         => (int) $h->totalClasses,
                'subjects'        => (int) $h->totalSubjects,
            ],

            'today' => [
                'student_pct'      => $studentAttPct,
                'students_present' => (int) $h->studentsPresentToday,
                'teacher_pct'      => $teacherAttPct,
                'teachers_present' => (int) $h->teachersPresentToday,
                'fee_today'        => (float) $h->feeCollectedToday,
                'collection_pct'   => $collectionPct,
                'total_fee'        => (float) $h->totalFee,
            ],

            // The last 30 days, oldest first, and today's figures under each chart.
            'attendance_trend' => [
                'days' => array_values($h->last15DaysData),
                'pick' => $pick ?: null,
            ],

            'fee' => [
                'range_label' => $h->feeRangeLabel(),
                'range_total' => (float) $h->feeRangeTotal,
                'labels'      => $h->feeSeries['labels'] ?? [],
                'data'        => $h->feeSeries['data'] ?? [],
                'collected'   => (float) $h->overallFeeCollected,
                'pending'     => (float) $h->feeRemaining,
                'expected'    => (float) $h->totalFee,
                'today'       => (float) $h->feeCollectedToday,
                'progress'    => $collectionPct,
            ],

            // Today first, then each day before it, as the table reads.
            'week' => array_reverse(array_values($h->last7DaysData)),

            'exam' => [
                'trend'  => $exams,
                'avg'    => $h->examTrendAvg,
                'delta'  => $h->examTrendDelta,
                'latest' => $latest,
            ],

            'events' => array_values($h->upcomingEvents),

            'queries' => [
                'students' => (int) $h->studentQueries,
                'teachers' => (int) $h->teacherQueries,
                'website'  => (int) $h->websiteQueries,
            ],

            'rating' => [
                'average' => (float) $h->averageRating,
                'total'   => (int) $h->totalRatings,
                'latest'  => $rating ? [
                    'rating'   => (int) $rating->rating,
                    // Rate LMS keeps the words JSON-encoded ("\"Works well\"").
                    'feedback' => is_string($rating->feedback) && is_string($words = json_decode($rating->feedback, true))
                        ? $words
                        : $rating->feedback,
                    'date'     => Carbon::parse($rating->created_at)->format('d M Y'),
                ] : null,
            ],
        ]);
    }

    // ── Analytics ──────────────────────────────────────────────────────────────
    public function analytics(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return $err;

        $a = new Analytics();
        $a->mount();

        // Student Performance: a class of the school, then one of its sections.
        $class = (string) $request->query('class', '');
        if ($class !== '' && $a->standards->contains('id', (int) $class)) {
            $a->performerClass = $class;
            $a->updatedPerformerClass();

            $section = (string) $request->query('section', '');
            if ($section !== '' && collect($a->sections)->contains('id', (int) $section)) {
                $a->performerSection = $section;
                $a->updatedPerformerSection();
            }
        }

        // Admissions Trend: one of the school years the dropdown lists.
        $year = (string) $request->query('admission_year', '');
        if ($year !== '' && in_array((int) $year, array_map('intval', $a->admissionYears), true)) {
            $a->admissionYear = $year;
            $a->updatedAdmissionYear();
        }

        // WhatsApp: the period, kind and status picked, and how many rows.
        $a->waPeriod  = (string) $request->query('wa_period', '');
        $a->waFeature = array_key_exists((string) $request->query('wa_feature', ''), SchoolWhatsApp::FEATURES)
            ? (string) $request->query('wa_feature') : '';
        $a->waStatus  = in_array($request->query('wa_status'), ['sent', 'delivered', 'read', 'failed'], true)
            ? (string) $request->query('wa_status') : '';
        $a->waShown   = max(25, min(1000, (int) $request->query('wa_shown', 25)));

        return $this->success([
            'date' => now()->format('l, d M Y'),
            'today_label' => now()->format('d M Y'),

            'stats' => $a->statsData,
            'kpis'  => $a->kpis,

            'attendance' => [
                'daily_trend' => $a->attendanceDailyTrend,
                'split'       => $a->studentPieData,
                'monthly'     => $a->monthlyAttendancePct,
                'class_rank'  => array_values($a->classAttendanceRank),
            ],

            'performance' => [
                'standards' => $a->standards->map(fn ($s) => ['id' => (int) $s->id, 'name' => $s->name])->values(),
                'sections'  => collect($a->sections)->map(fn ($s) => ['id' => (int) $s->id, 'name' => $s->name])->values(),
                'class'     => $a->performerClass !== '' ? (int) $a->performerClass : null,
                'section'   => $a->performerSection !== '' ? (int) $a->performerSection : null,
                'top'       => array_values($a->topRankers),
                'bottom'    => array_values($a->bottomRankers),
            ],

            'admissions' => [
                'years' => array_values(array_map('intval', $a->admissionYears)),
                'year'  => (int) $a->admissionYear,
                'total' => (int) $a->admissionsTotal,
                'trend' => $a->admissionsTrend,
            ],

            'enquiries' => [
                'stats'  => $a->enquiryStats,
                'latest' => array_values($a->adminEnquiries),
            ],

            'fee' => [
                'stats'           => $a->feeStats,
                'by_class'        => $a->feeClassData,
                'rate_by_class'   => array_values($a->feeClassRate),
                'transport'       => $a->transportFeeStats,
                'transport_class' => $a->transportClassData,
                'transport_route' => $a->transportRouteData,
            ],

            'whatsapp' => $this->whatsApp($a),

            'operations' => [
                'arrangements'       => array_values($a->arrangements),
                'available_teachers' => array_values($a->availableTeachers),
            ],
        ]);
    }

    /**
     * The WhatsApp section as livewire.partials.whatsapp-analytics draws it
     * for the school admin (to read): its tiles, Messages a Day, Delivery and
     * the messages — or null when the panel leaves the section out.
     */
    private function whatsApp(Analytics $a): ?array
    {
        $wa = $a->whatsAppAnalytics();
        if (!$wa) {
            return null;
        }

        $features = SchoolWhatsApp::FEATURES;
        $money    = fn ($v) => '₹' . number_format((float) $v, fmod((float) $v, 1) ? 2 : 0);
        $usage    = $wa['usage'];
        $daily    = $wa['daily'];
        $rate     = $money($wa['rate']);
        $went     = array_sum($usage['counts']);
        $tried    = $went + $usage['failed'];

        $tiles = [['label' => 'Messages', 'value' => number_format($went), 'sub' => $wa['label']]];
        foreach ($features as $f => [$label, $charged, $switched]) {
            $tiles[] = ['label' => $label, 'value' => number_format($usage['counts'][$f] ?? 0), 'sub' => match (true) {
                $switched => (($wa['switches'][$f] ?? false) ? 'On' : 'Off') . ' · ' . $rate . ' each',
                $charged  => 'Send Credentials · ' . $rate . ' each',
                default   => 'On adding someone',
            }];
        }
        $tiles[] = [
            'label' => 'Charged',
            'value' => $money($usage['amount']),
            'sub'   => number_format($usage['charged']) . ' charged'
                . ($usage['failed'] ? ' · ' . number_format($usage['failed']) . ' not delivered' : ''),
        ];

        $days    = max(1, count($daily['days']));
        $busiest = $daily['total'] ? array_keys($daily['total'], max($daily['total']))[0] : null;

        $statuses = ['read' => 'Read', 'delivered' => 'Delivered', 'sent' => 'Sent', 'failed' => 'Not delivered'];
        $delivery = [];
        foreach ($statuses as $key => $label) {
            $n = (int) ($usage['statuses'][$key] ?? 0);
            $delivery[] = ['key' => $key, 'label' => $label, 'count' => $n, 'share' => $tried > 0 ? round($n / $tried * 100) : 0];
        }

        $periods = [];
        foreach ($wa['periods'] as $value => $name) {
            $periods[] = ['value' => (string) $value, 'label' => $name];
        }

        $switches = [];
        foreach ($wa['switches'] as $f => $on) {
            $switches[] = ['key' => $f, 'label' => $f === 'credentials' ? 'Send Credentials' : $features[$f][0], 'on' => (bool) $on];
        }

        $kinds = [];
        foreach ($features as $f => [$label]) {
            $kinds[] = ['key' => $f, 'label' => $label, 'data' => array_values($daily['counts'][$f] ?? [])];
        }

        $list = $wa['messages'];

        return [
            'period'    => $wa['period'],
            'label'     => $wa['label'],
            'periods'   => $periods,
            'switches'  => $switches,
            'rate'      => $rate,
            'tiles'     => $tiles,
            'daily'     => [
                'labels' => $daily['labels'],
                'kinds'  => $kinds,
                'total'  => array_values($daily['total']),
                'avg'    => number_format($went / $days, 1),
            ],
            'delivery'  => $delivery,
            'busiest'   => $busiest !== null && $daily['total'][$busiest] > 0
                ? ['label' => $daily['labels'][$busiest], 'count' => (int) $daily['total'][$busiest]]
                : null,
            'feature'   => $a->waFeature,
            'status'    => $a->waStatus,
            'shown'     => $a->waShown,
            'messages'  => [
                'count' => (int) $list['count'],
                'rows'  => array_map(fn ($r) => [
                    'id'      => $r['id'],
                    'date'    => $r['at']?->format('d M Y'),
                    'time'    => $r['at']?->format('h:i A'),
                    'feature' => $r['feature'],
                    'label'   => $r['label'],
                    'name'    => $r['name'],
                    'phone'   => $r['phone'],
                    'status'  => $r['status'],
                    'error'   => $r['error'],
                    'charge'  => match (true) {
                        $r['charge'] === 'free' => 'Free',
                        $r['charge'] === 'none' => 'Not charged',
                        default                 => $money($r['charge']),
                    },
                    'charged' => is_float($r['charge']),
                ], $list['rows']),
            ],
        ];
    }

    // ── Operations: Assign ─────────────────────────────────────────────────────
    /**
     * Today's Absent Teachers — Arrangement: a substitute for one period, as
     * the panel's Assign button sets it (Analytics::saveArrangement), kept to
     * the school's own arrangements and teachers.
     */
    public function assignArrangement(Request $request, int $id)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return $err;
        if ($err = $this->validateWith($request, ['teacher_id' => 'required|integer'])) return $err;

        $orgId = (int) $user->organization_id;

        $teacher = TeacherDetail::where('organization_id', $orgId)->find((int) $request->input('teacher_id'));
        if (!$teacher) {
            return $this->error('Teacher not found.', 404);
        }

        $arrangement = TeacherArrangement::where('id', $id)
            ->whereHas('originalTeacher', fn ($q) => $q->where('organization_id', $orgId))
            ->first();
        if (!$arrangement) {
            return $this->error('Arrangement not found.', 404);
        }

        TeacherArrangement::where('id', $arrangement->id)->update(['substitute_teacher_id' => $teacher->id]);

        return $this->success([
            'id'                    => $arrangement->id,
            'substitute_teacher_id' => $teacher->id,
            'status'                => 'assigned',
        ], 'Saved');
    }
}
