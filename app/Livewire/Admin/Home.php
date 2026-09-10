<?php

namespace App\Livewire\Admin;

use App\Models\Admin\AdminEnquiry;
use App\Models\Admin\ContactAdminStudent;
use App\Models\Admin\ContactAdminTeacher;
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
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Illuminate\Support\Facades\Session;
use App\Models\Admin\RateLms;

class Home extends Component
{
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
    public string $feeRange = '7';
    public $feeSeries = [];
    public $feeRangeTotal = 0;

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

        // Today's Attendance
        $today = now()->format('Y-m-d');

        $this->studentsPresentToday = StudentAttendance::whereHas('studentDetail', function ($query) {
            $query->forOrganization();
        })
            ->whereDate('attendance_date', $today)
            ->where('status', true)
            ->count();

        $this->studentsAbsentToday = StudentAttendance::whereHas('studentDetail', function ($query) {
            $query->forOrganization();
        })
            ->whereDate('attendance_date', $today)
            ->where('status', false)
            ->count();

        $this->teachersPresentToday = TeacherAttendance::whereHas('teacherDetail', function ($query) {
            $query->forOrganization();
        })
            ->whereDate('attendance_date', $today)
            ->where('status', true)
            ->count();

        $this->teachersAbsentToday = TeacherAttendance::whereHas('teacherDetail', function ($query) {
            $query->forOrganization();
        })
            ->whereDate('attendance_date', $today)
            ->where('status', false)
            ->count();

        // Fee Statistics using new simplified models
        $orgId = FacadesAuth::user()->organization_id;

        $this->overallFeeCollected = FeePayment::where('organization_id', $orgId)->sum('amount');
        $totalFeeStructure = FeeStructure::where('organization_id', $orgId)->where('is_active', true)->sum('amount');
        $this->totalFee = $totalFeeStructure;
        $this->feeRemaining = max(0, $totalFeeStructure - $this->overallFeeCollected);

        $this->feeCollectedToday = FeePayment::where('organization_id', $orgId)
            ->whereDate('payment_date', $today)
            ->sum('amount');
    }

    protected function loadLast7DaysData()
    {
        $this->last7DaysData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayName = now()->subDays($i)->format('D');

            // Student Attendance
            $studentPresent = StudentAttendance::whereHas('studentDetail', function ($query) {
                $query->forOrganization();
            })
                ->whereDate('attendance_date', $date)
                ->where('status', true)
                ->count();

            $studentAbsent = StudentAttendance::whereHas('studentDetail', function ($query) {
                $query->forOrganization();
            })
                ->whereDate('attendance_date', $date)
                ->where('status', false)
                ->count();

            // Teacher Attendance
            $teacherPresent = TeacherAttendance::whereHas('teacherDetail', function ($query) {
                $query->forOrganization();
            })
                ->whereDate('attendance_date', $date)
                ->where('status', true)
                ->count();

            $teacherAbsent = TeacherAttendance::whereHas('teacherDetail', function ($query) {
                $query->forOrganization();
            })
                ->whereDate('attendance_date', $date)
                ->where('status', false)
                ->count();

            // Fee Collected
            $feeCollected = FeePayment::where('organization_id', FacadesAuth::user()->organization_id)
                ->whereDate('payment_date', $date)
                ->sum('amount');

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
    protected function loadLast15DaysData(): void
    {
        $orgId = FacadesAuth::user()->organization_id;

        $stu = StudentAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', '>=', now()->subDays(14)->toDateString())
            ->selectRaw('DATE(attendance_date) as d, status, COUNT(*) as c')
            ->groupBy('d', 'status')->get();

        $tch = TeacherAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', '>=', now()->subDays(14)->toDateString())
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
        for ($i = 14; $i >= 0; $i--) {
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

        $payments = FeePayment::where('organization_id', $orgId)
            ->whereDate('payment_date', '>=', $start->toDateString())
            ->selectRaw('DATE(payment_date) as d, SUM(amount) as total')
            ->groupBy('d')->pluck('total', 'd');

        $byDate = [];
        foreach ($payments as $d => $total) {
            $byDate[(string) $d] = (float) $total;
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

    protected function loadUpcomingEvents()
    {
        $this->upcomingEvents = TimeTable::forOrganization()
            ->where('date', '>=', now()->format('Y-m-d'))
            ->where('is_cancelled', false)
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->take(6)
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
