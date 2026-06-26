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
        'admin.quiz' => 'Quizzes',
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
