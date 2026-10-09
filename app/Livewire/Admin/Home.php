<?php

namespace App\Livewire\Admin;

use App\Models\Admin\AdminEnquiry;
use App\Models\Admin\ContactAdminStudent;
use App\Models\Admin\ContactAdminTeacher;
use App\Models\Admin\ExamCopy;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Calendar\TimeTable;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use App\Services\GradingService;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Illuminate\Support\Facades\Session;
use App\Models\Admin\RateLms;
use App\Models\Admin\TransportFeePayment;
use App\Livewire\Concerns\CountsBillableMonths;
use App\Support\AcademicYear;

class Home extends Component
{
    use CountsBillableMonths;

    public $searchQuery = '';
    public $searchResults = [];
    public $recentSearches = [];

    // Statistics
    public $totalStudents = 0;
    public $activeStudents = 0;
    public $inactiveStudents = 0;
    public $totalTeachers = 0;
    public $activeTeachers = 0;
    public $inactiveTeachers = 0;
    public $totalClasses = 0;
    public $totalSubjects = 0;

    // Today's data
    public $studentsPresentToday = 0;
    public $studentsAbsentToday = 0;
    public $teachersPresentToday = 0;
    public $teachersAbsentToday = 0;

    // Fee data
    public $totalFee = 0;
    public $feeCollectedToday = 0;
    public $feeRemaining = 0;
    public $overallFeeCollected = 0;
    public $organization;
    public $studentQueries;
    public $teacherQueries;
    public $websiteQueries;

    public $latestRating;
    public $averageRating;
    public $totalRatings;

    // Last 7 days data
    public $last7DaysData = [];

    // ── Attendance trend (student and teacher read separately) ───────────────
    // The charts plot the last 15 days; the dropdown picks one of those days
    // and the card underneath reports that day on its own.
    public $last15DaysData = [];
    public string $attTrendDate = '';

    // ── Fee collection ───────────────────────────────────────────────────────
    // 7 / 15 / 30 / 60 / 90 days, or 180 for the last six months. Short ranges
    // bucket by day, longer ones by week and the six-month one by month, so the
    // bars stay readable whatever is picked.
    // The Fee Collection chart is the last 30 days; its range picker is off the page.
    public string $feeRange = '30';
    public $feeSeries = [];
    public $feeRangeTotal = 0;

    // -- Exam performance ----------------------------------------------------
    // Average percentage scored in each of the last few exams, oldest first, so
    // the line reads left to right and says whether results are climbing.
    public $examTrend = [];
    public float $examTrendAvg = 0;
    public float $examTrendDelta = 0;

    // Upcoming events
    public $upcomingEvents = [];

    protected $routeLabels = [
        'admin.home' => 'Dashboard',
        'admin.standard' => 'Class Management',
        'admin.student' => 'Student Management',
        'admin.teacher' => 'Teacher Management',
        'admin.announcement' => 'Announcements',
        'admin.timetable' => 'Class Timetable',
        'admin.arrangement' => 'Teacher Arrangements',
        'admin.fee' => 'Fee Management',
        'admin.homework' => 'Homework',
        'admin.attendance' => 'Attendance',
        'admin.syllabus' => 'Syllabus',
        'admin.calender' => 'School Calendar',
        'admin.rules-and-regulation' => 'School Rules',
        'admin.content' => 'Learning Content',
        'admin.performance' => 'Performance Reports',
        'admin.analytics' => 'Analytics',
        'admin.assignments' => 'Assignments',
        'admin.library' => 'Library',
        'admin.support' => 'Support',
        'admin.id-card' => 'ID Cards',
        'admin.admit-card' => 'Admit Cards',
        'admin.seating-plan' => 'Exam Seating Plan',
        'admin.exam-copy' => 'Exam Papers',
        'admin.report-card' => 'Report Cards',
        'admin.contact-admin' => 'Contact Admin',
        'admin.about-app' => 'About LMS',
        'admin.rate-lms' => 'Rate LMS',
        'admin.enqueries' => 'Enquiries',
        'admin.terms-and-condition' => 'Terms & Conditions',
        'admin.profile' => 'My Profile',
        'admin.notification' => 'Notifications',
    ];

    public function mount()
    {
        $this->recentSearches = Session::get('admin_recent_searches', []);
        $this->loadStatistics();
        $this->organization = FacadesAuth::user()->organization_id;
        $this->loadLast7DaysData();
        $this->attTrendDate = now()->toDateString();
        $this->loadLast15DaysData();
        $this->loadFeeSeries();
        $this->loadExamTrend();
        $this->loadUpcomingEvents();
        $this->teacherQueries = ContactAdminTeacher::forOrganization()->count();
        $this->studentQueries = ContactAdminStudent::forOrganization()->count();
        $this->websiteQueries = AdminEnquiry::forOrganization()->count();
        $this->loadLmsRatings();
    }

    protected function loadLmsRatings()
    {
        $this->latestRating = RateLms::forOrganization()
            ->latest()
            ->first();

        $ratingStats = RateLms::forOrganization()
            ->select(
                DB::raw('COUNT(*) as total_ratings'),
                DB::raw('AVG(rating) as average_rating')
            )
            ->first();

        $this->totalRatings = $ratingStats->total_ratings ?? 0;
        $this->averageRating = round($ratingStats->average_rating ?? 0, 1);
    }

    protected function loadStatistics()
    {
        // Student Statistics
        $this->totalStudents = StudentDetail::forOrganization()->count();
        $this->activeStudents = User::where('role', 'user')
            ->forOrganization()
            ->where('is_active', true)
            ->count();
        $this->inactiveStudents = User::forOrganization()
            ->where('role', 'user')
            ->where('is_active', false)
            ->count();;

        // Teacher Statistics
        $this->totalTeachers = TeacherDetail::forOrganization()->count();
        $this->activeTeachers = User::forOrganization()
            ->where('role', 'teacher')
            ->where('is_active', 1)
            ->count();
        $this->inactiveTeachers = $this->totalTeachers - $this->activeTeachers;

        // Classes and Sections
        $this->totalClasses = Standard::forOrganization()->count();

        // Total Subjects
        $this->totalSubjects = Subject::forOrganization()
            ->where('is_active', 1)
            ->count();

        // Today's Attendance — read from the week's counts (weekMarks), which
        // count exactly the rows these four queries used to.
        $today = now()->format('Y-m-d');
        $marks = $this->weekMarks();

        $this->studentsPresentToday = $marks['students'][$today][1] ?? 0;
        $this->studentsAbsentToday  = $marks['students'][$today][0] ?? 0;
        $this->teachersPresentToday = $marks['teachers'][$today][1] ?? 0;
        $this->teachersAbsentToday  = $marks['teachers'][$today][0] ?? 0;

        $this->loadFeeOverview();
    }

    /** The last seven days' marks and fee, read once per load (see weekMarks). */
    private ?array $weekMarks = null;

    /**
     * The last seven days, today included, as the dashboard has always counted
     * them — attendance of the school's own students and teachers (through
     * their details), present 1 and absent 0, and every fee payment of the
     * school — but in one grouped query each instead of four counts and a sum
     * for every day: [date => [status => count]] and [date => amount].
     */
    private function weekMarks(): array
    {
        if ($this->weekMarks !== null) {
            return $this->weekMarks;
        }

        $from = now()->subDays(6)->format('Y-m-d');
        $to   = now()->format('Y-m-d');

        $byDay = function ($query, string $relation) use ($from, $to): array {
            $out = [];
            $rows = $query->whereHas($relation, function ($q) {
                $q->forOrganization();
            })
                ->whereDate('attendance_date', '>=', $from)
                ->whereDate('attendance_date', '<=', $to)
                ->whereIn('status', [0, 1])
                ->selectRaw('DATE(attendance_date) as d, status, COUNT(*) as c')
                ->groupBy('d', 'status')
                ->get();
            foreach ($rows as $r) {
                $out[(string) $r->d][(int) $r->status] = (int) $r->c;
            }
            return $out;
        };

        $fee = FeePayment::where('organization_id', FacadesAuth::user()->organization_id)
            ->whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->selectRaw('DATE(payment_date) as d, SUM(amount) as total')
            ->groupBy('d')
            ->pluck('total', 'd')
            ->mapWithKeys(fn ($total, $d) => [(string) $d => $total])
            ->all();

        return $this->weekMarks = [
            'students' => $byDay(StudentAttendance::query(), 'studentDetail'),
            'teachers' => $byDay(TeacherAttendance::query(), 'teacherDetail'),
            'fee'      => $fee,
        ];
    }

    /**
     * The school's fee, counted as Fee → Analytics counts it: every student's
     * academic fee (the active heads of their class — the whole class's and
     * their own section's — plus their own Last Year Dues) and every rider's
     * transport fee (route fee × the months they are billed for). Collected is
     * academic and transport, from both payment tables, taken this session
     * (1 April) up to today — a payment dated in an earlier session, or one
     * dated ahead of today, is not this session's collection.
     */
    protected function loadFeeOverview(): void
    {
        $orgId = FacadesAuth::user()->organization_id;

        $heads = FeeStructure::where('organization_id', $orgId)
            ->where('is_active', true)->where('fee_type', 'academic')
            ->get(['standard_id', 'section_id', 'amount'])
            ->groupBy('standard_id');

        // Students per class and section, so each pair's fee is worked out once.
        $pairs = StudentDetail::where('organization_id', $orgId)
            ->selectRaw('standard_id, section_id, COUNT(*) as students')
            ->groupBy('standard_id', 'section_id')
            ->get();

        $academic = 0.0;
        foreach ($pairs as $pair) {
            $fee = ($heads[$pair->standard_id] ?? collect())
                ->filter(fn ($h) => $h->section_id === null || (int) $h->section_id === (int) $pair->section_id)
                ->sum('amount');
            $academic += (float) $fee * (int) $pair->students;
        }
        $academic += FeeStructure::ownTotalForSchool($orgId); // students' own Last Year Dues

        $transport = 0.0;
        $riders = DB::table('transportation_students as ts')
            ->join('transportations as t', 'ts.transportation_id', '=', 't.id')
            ->join('student_details as sd', 'ts.student_detail_id', '=', 'sd.id')
            ->where('ts.organization_id', $orgId)
            ->get(['ts.billable_months', 't.monthly_fee']);
        foreach ($riders as $row) {
            $transport += (float) $row->monthly_fee * $this->billableMonthsCount($row->billable_months);
        }

        $this->totalFee            = $academic + $transport;
        $today = now()->toDateString();

        $this->overallFeeCollected = $this->feeCollected($orgId, AcademicYear::start()->toDateString(), $today);
        $this->feeRemaining        = max(0, $this->totalFee - $this->overallFeeCollected);
        $this->feeCollectedToday   = $this->feeCollected($orgId, $today, $today);
    }

    /** Academic and transport fee taken between two dates, both included. */
    private function feeCollected(int $orgId, string $from, string $to): float
    {
        $academicAndTransport = FeePayment::where('organization_id', $orgId)
            ->whereIn('fee_type', ['academic', 'transport'])
            ->whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->sum('amount');

        $transportTable = TransportFeePayment::where('organization_id', $orgId)
            ->whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->sum('amount');

        return round((float) $academicAndTransport + (float) $transportTable, 2);
    }

    protected function loadLast7DaysData()
    {
        $this->last7DaysData = [];
        $marks = $this->weekMarks();

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayName = now()->subDays($i)->format('D');

            // Student Attendance
            $studentPresent = $marks['students'][$date][1] ?? 0;
            $studentAbsent  = $marks['students'][$date][0] ?? 0;

            // Teacher Attendance
            $teacherPresent = $marks['teachers'][$date][1] ?? 0;
            $teacherAbsent  = $marks['teachers'][$date][0] ?? 0;

            // Fee Collected (a day without payments is 0, as sum() gave)
            $feeCollected = ($marks['fee'][$date] ?? null) ?: 0;

            $this->last7DaysData[] = [
                'date' => $date,
                'day' => $dayName,
                'student_present' => $studentPresent,
                'student_absent' => $studentAbsent,
                'student_total' => $studentPresent + $studentAbsent,
                'teacher_present' => $teacherPresent,
                'teacher_absent' => $teacherAbsent,
                'teacher_total' => $teacherPresent + $teacherAbsent,
                'fee_collected' => $feeCollected,
            ];
        }
    }

    /**
     * Fifteen days of attendance, oldest first — one bucket per day carrying the
     * student and teacher counts separately so each trend chart reads on its own.
     */
    /**
     * The Attendance Trend's days, the oldest first. It covers the last 30 days
     * now (the name is from when it was 15); with no date picker on the page
     * any more, the figures under each chart are today's.
     */
    private const TREND_DAYS = 30;

    protected function loadLast15DaysData(): void
    {
        $orgId = FacadesAuth::user()->organization_id;

        $stu = StudentAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', '>=', now()->subDays(self::TREND_DAYS - 1)->toDateString())
            ->selectRaw('DATE(attendance_date) as d, status, COUNT(*) as c')
            ->groupBy('d', 'status')->get();

        $tch = TeacherAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', '>=', now()->subDays(self::TREND_DAYS - 1)->toDateString())
            ->selectRaw('DATE(attendance_date) as d, status, COUNT(*) as c')
            ->groupBy('d', 'status')->get();

        // [date => [status => count]] so each day is one array lookup, not a query.
        $bucket = function ($rows) {
            $out = [];
            foreach ($rows as $r) {
                $out[(string) $r->d][(int) $r->status] = (int) $r->c;
            }
            return $out;
        };
        $stuBy = $bucket($stu);
        $tchBy = $bucket($tch);

        $this->last15DaysData = [];
        for ($i = self::TREND_DAYS - 1; $i >= 0; $i--) {
            $day  = now()->subDays($i);
            $date = $day->toDateString();
            $s    = $stuBy[$date] ?? [];
            $t    = $tchBy[$date] ?? [];

            $sPresent = $s[1] ?? 0;
            $sAbsent  = $s[0] ?? 0;
            $sHalf    = $s[2] ?? 0;
            $tPresent = $t[1] ?? 0;
            $tAbsent  = $t[0] ?? 0;
            $tHalf    = $t[2] ?? 0;

            $sMarked = $sPresent + $sAbsent + $sHalf;
            $tMarked = $tPresent + $tAbsent + $tHalf;

            $this->last15DaysData[] = [
                'date'            => $date,
                'label'           => $day->format('d M'),
                'day'             => $day->format('D'),
                'student_present' => $sPresent,
                'student_absent'  => $sAbsent,
                'student_half'    => $sHalf,
                'student_total'   => $sMarked,
                'student_pct'     => $sMarked > 0 ? round(($sPresent + 0.5 * $sHalf) / $sMarked * 100, 1) : 0,
                'teacher_present' => $tPresent,
                'teacher_absent'  => $tAbsent,
                'teacher_half'    => $tHalf,
                'teacher_total'   => $tMarked,
                'teacher_pct'     => $tMarked > 0 ? round(($tPresent + 0.5 * $tHalf) / $tMarked * 100, 1) : 0,
            ];
        }
    }

    /** The row from the 15-day set that the trend dropdown is pointing at. */
    public function trendDay(): array
    {
        foreach ($this->last15DaysData as $row) {
            if ($row['date'] === $this->attTrendDate) {
                return $row;
            }
        }

        return end($this->last15DaysData) ?: [];
    }

    public function updatedFeeRange(): void
    {
        $this->loadFeeSeries();
    }

    /**
     * Fee actually collected over the selected range, bucketed so the bar chart
     * stays legible: by day up to 30, by week to 90, by month for six months.
     */
    protected function loadFeeSeries(): void
    {
        $orgId = FacadesAuth::user()->organization_id;
        $days  = (int) $this->feeRange;
        $start = now()->subDays($days - 1)->startOfDay();

        // Academic and transport, from both payment tables.
        $byDate = [];
        $queries = [
            FeePayment::where('organization_id', $orgId)->whereIn('fee_type', ['academic', 'transport']),
            TransportFeePayment::where('organization_id', $orgId),
        ];
        foreach ($queries as $query) {
            $payments = $query->whereDate('payment_date', '>=', $start->toDateString())
                ->selectRaw('DATE(payment_date) as d, SUM(amount) as total')
                ->groupBy('d')->pluck('total', 'd');

            foreach ($payments as $d => $total) {
                $byDate[(string) $d] = ($byDate[(string) $d] ?? 0) + (float) $total;
            }
        }

        // Bucket width in days: 1 (daily), 7 (weekly) or a whole month.
        $monthly = $days > 90;
        $step    = $days <= 30 ? 1 : 7;

        $labels = $values = [];

        if ($monthly) {
            $cursor = now()->subMonthsNoOverflow(5)->startOfMonth();
            for ($i = 0; $i < 6; $i++) {
                $mStart = $cursor->copy()->startOfMonth();
                $mEnd   = $cursor->copy()->endOfMonth();
                $labels[] = $mStart->format('M y');
                $values[] = $this->sumBetween($byDate, $mStart, $mEnd);
                $cursor->addMonthNoOverflow();
            }
        } else {
            for ($offset = $days - 1; $offset >= 0; $offset -= $step) {
                $bStart = now()->subDays($offset)->startOfDay();
                $bEnd   = now()->subDays(max(0, $offset - $step + 1))->endOfDay();
                if ($bEnd->gt(now())) $bEnd = now()->endOfDay();

                $labels[] = $step === 1
                    ? $bStart->format('d M')
                    : $bStart->format('d M') . '–' . $bEnd->format('d M');
                $values[] = $this->sumBetween($byDate, $bStart, $bEnd);
            }
        }

        $this->feeSeries     = ['labels' => $labels, 'data' => $values];
        $this->feeRangeTotal = array_sum($values);
    }

    /** Total from the date=>amount map that falls inside a window. */
    private function sumBetween(array $byDate, $from, $to): float
    {
        $sum = 0.0;
        foreach ($byDate as $date => $amount) {
            if ($date >= $from->toDateString() && $date <= $to->toDateString()) {
                $sum += $amount;
            }
        }
        return $sum;
    }

    /** Human label for the active fee range, used in the card header. */
    public function feeRangeLabel(): string
    {
        return match ((int) $this->feeRange) {
            15  => 'Last 15 days',
            30  => 'Last 30 days',
            60  => 'Last 60 days',
            90  => 'Last 90 days',
            180 => 'Last 6 months',
            default => 'Last 7 days',
        };
    }

    /**
     * The last eight exams that actually carry marks, oldest first: one point
     * per exam holding the average percentage across every paper marked for it,
     * plus the pass rate on the current grading scale. Absentees are left out
     * so an empty answer sheet never drags the average down.
     */
    protected function loadExamTrend(): void
    {
        $orgId = FacadesAuth::user()->organization_id;
        $pass  = app(GradingService::class)->passPercentage();

        // The latest eight exams that have marks.
        $exams = ExamCopy::query()
            ->join('exams', 'exams.id', '=', 'exam_copies.exam_id')
            ->where('exam_copies.organization_id', $orgId)
            ->selectRaw('exams.id as exam_id, exams.exam_name, exams.start_date, exams.end_date')
            ->groupBy('exams.id', 'exams.exam_name', 'exams.start_date', 'exams.end_date')
            ->orderByRaw('COALESCE(exams.end_date, exams.start_date) DESC, exams.id DESC')
            ->limit(8)
            ->get();

        // Each student's overall marks in each of those exams: every paper of
        // theirs added up. A paper they were absent for counts as nought out of
        // its full marks — it is marks they did not score.
        $perStudent = ExamCopy::query()
            ->where('organization_id', $orgId)
            ->whereIn('exam_id', $exams->pluck('exam_id'))
            ->selectRaw(
                'exam_id, student_detail_id,'
                . ' SUM(CASE WHEN is_absent = 1 THEN 0 ELSE COALESCE(marks_obtained, 0) END) as obtained,'
                . ' SUM(COALESCE(max_marks, 0)) as max_marks,'
                . ' COUNT(*) as papers'
            )
            ->groupBy('exam_id', 'student_detail_id')
            ->get()
            ->groupBy('exam_id');

        $trend = $exams->reverse()->values()->map(function ($r) use ($perStudent, $pass) {
            $when = $r->end_date ?: $r->start_date;
            $name = $r->exam_name ?: 'Exam #' . $r->exam_id;

            // Students with marks to their name in this exam, each as a percentage.
            $scores = ($perStudent[$r->exam_id] ?? collect())
                ->filter(fn ($s) => (float) $s->max_marks > 0)
                ->map(fn ($s) => (float) $s->obtained / (float) $s->max_marks * 100);

            $students = $scores->count();
            $passed   = $scores->filter(fn ($pct) => $pct >= $pass)->count();

            return [
                'exam'     => $name,
                'label'    => \Illuminate\Support\Str::limit($name, 16),
                'date'     => $when ? \Carbon\Carbon::parse($when)->format('d M y') : '--',
                // The exam's overall average: every student's overall percentage, averaged.
                'avg'      => $students > 0 ? round($scores->avg(), 1) : 0,
                // The share of those students who passed on their overall marks.
                'pass_pct' => $students > 0 ? round($passed / $students * 100, 1) : 0,
                'passed'   => $passed,
                'papers'   => (int) ($perStudent[$r->exam_id] ?? collect())->sum('papers'),
                'students' => $students,
            ];
        })->toArray();

        $this->examTrend = $trend;

        $count = count($trend);
        $this->examTrendAvg   = $count > 0 ? round(array_sum(array_column($trend, 'avg')) / $count, 1) : 0;
        $this->examTrendDelta = $count >= 2 ? round($trend[$count - 1]['avg'] - $trend[$count - 2]['avg'], 1) : 0;
    }

    protected function loadUpcomingEvents()
    {
        $this->upcomingEvents = TimeTable::forOrganization()
            ->where('date', '>=', now()->format('Y-m-d'))
            ->where('is_cancelled', false)
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            // Two at a time: the next two on the calendar.
            ->take(2)
            ->get()
            ->map(function ($event) {
                return [
                    'title' => $event->title,
                    'description' => $event->description,
                    'date' => $event->date,
                    'formatted_date' => now()->parse($event->date)->format('d M'),
                    'start_time' => $event->start_time ? now()->parse($event->start_time)->format('g:i A') : null,
                    'end_time' => $event->end_time ? now()->parse($event->end_time)->format('g:i A') : null,
                    'is_all_day' => $event->is_all_day,
                    'color' => $this->getEventColor($event->event_type ?? 'other'),
                    'event_type' => $event->event_type,
                    'location' => $event->location?->location_display,
                ];
            })
            ->toArray();
    }

    protected function getEventColor($type)
    {
        return match ($type) {
            'exam', 'test' => 'blue',
            'meeting', 'ptm' => 'green',
            'holiday', 'leave' => 'red',
            'event', 'celebration', 'annual_day' => 'purple',
            'sports', 'competition' => 'orange',
            'workshop', 'training' => 'indigo',
            default => 'gray',
        };
    }

    public function updatedSearchQuery()
    {
        if (empty($this->searchQuery)) {
            $this->searchResults = [];
            return;
        }

        $query = strtolower($this->searchQuery);
        $this->searchResults = [];

        foreach ($this->routeLabels as $route => $label) {
            if (str_contains(strtolower($label), $query)) {
                $this->searchResults[$route] = $label;
            }
        }
    }

    public function selectResult($route)
    {
        if (isset($this->routeLabels[$route])) {
            $this->addToRecentSearches($this->routeLabels[$route]);
            return redirect()->route($route, ['organization' => FacadesAuth::user()->organization]);
        }
    }

    protected function addToRecentSearches($searchTerm)
    {
        $searches = Session::get('admin_recent_searches', []);
        $searches = array_filter($searches, fn($item) => $item['term'] !== $searchTerm);
        array_unshift($searches, [
            'term' => $searchTerm,
            'time' => now(),
        ]);
        $searches = array_slice($searches, 0, 10);
        Session::put('admin_recent_searches', $searches);
        $this->recentSearches = $searches;
    }

    public function clearRecentSearches()
    {
        Session::forget('admin_recent_searches');
        $this->recentSearches = [];
    }

    public function render()
    {
        return view('livewire.admin.home');
    }
}
