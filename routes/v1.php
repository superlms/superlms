<?php

use App\Http\Controllers\SendNotificationController;
use App\Http\Controllers\v1\AnnouncementController;
use App\Http\Controllers\v1\AttendanceController;
use App\Http\Controllers\v1\AuthController;
use App\Http\Controllers\v1\BookController;
use App\Http\Controllers\v1\CalendarController;
use App\Http\Controllers\v1\ContentController;
use App\Http\Controllers\v1\DashboardController;
use App\Http\Controllers\v1\ExamController;
use App\Http\Controllers\v1\FeeController;
use App\Http\Controllers\v1\FilterController;
use App\Http\Controllers\v1\HomeWorkController;
use App\Http\Controllers\v1\IdCardController;
use App\Http\Controllers\v1\InstructorController;
use App\Http\Controllers\v1\LibraryController;
use App\Http\Controllers\v1\AccountsController;
use App\Http\Controllers\v1\AdminController;
use App\Http\Controllers\v1\AdminProfileController;
use App\Http\Controllers\v1\AdminContentController;
use App\Http\Controllers\v1\AdminStandardController;
use App\Http\Controllers\v1\AdminStudentController;
use App\Http\Controllers\v1\AdminTeacherController;
use App\Http\Controllers\v1\AdminIdCardController;
use App\Http\Controllers\v1\AdminExamController;
use App\Http\Controllers\v1\AdminSyllabusController;
use App\Http\Controllers\v1\AdminChapterContentController;
use App\Http\Controllers\v1\AdminQuizController;
use App\Http\Controllers\v1\AdminBookController;
use App\Http\Controllers\v1\AdminTimetableController;
use App\Http\Controllers\v1\AdminArrangementController;
use App\Http\Controllers\v1\AdminHomeworkController;
use App\Http\Controllers\v1\AdminAttendanceController;
use App\Http\Controllers\v1\AdminTransportController;
use App\Http\Controllers\v1\AdminCreditController;
use App\Http\Controllers\v1\AdminAdmitCardController;
use App\Http\Controllers\v1\AdminReportCardController;
use App\Http\Controllers\v1\AdminTcCertificateController;
use App\Http\Controllers\v1\McqController;
use App\Http\Controllers\v1\PaymentController;
use App\Http\Controllers\PhonePeController;
use App\Http\Controllers\v1\ReportCardController;
use App\Http\Controllers\v1\Student\ExamCopyController as StudentExamCopyController;
use App\Http\Controllers\v1\Student\MarksController as StudentMarksController;
use App\Http\Controllers\v1\Teacher\ExamCopyController as TeacherExamCopyController;
use App\Http\Controllers\v1\Teacher\MarksController as TeacherMarksController;
use App\Http\Controllers\v1\SeatingPlanController;
use App\Http\Controllers\v1\StudentContactController;
use App\Http\Controllers\v1\SubjectController;
use App\Http\Controllers\v1\SyllabusController;
use App\Http\Controllers\v1\SwitchAccountController;
use App\Http\Controllers\v1\TeacherContactController;
use App\Http\Controllers\v1\TeacherController;
use App\Http\Controllers\v1\TimeTableController;
use App\Http\Controllers\v1\TransportController;
use App\Http\Controllers\v1\UserController;
use Illuminate\Support\Facades\Route;

//Unauthentication Route
Route::get('/unauthenticate', [AuthController::class, 'unauthenticate'])->name('unauthenticate');

//v1 version
Route::prefix('v1')->group(function () {
    // Login: per-email rate limit defined in AppServiceProvider.
    Route::middleware('throttle:login')->group(function () {
        // Unified login — auto-detects role from the identifier (admission no / email).
        Route::post('/login', [AuthController::class, 'login']);
        // Legacy per-role endpoints (kept for backward compatibility).
        Route::post('/user/login', [UserController::class, 'studentLogin']);
        Route::post('/teacher/login', [TeacherController::class, 'teacherLogin']);
        Route::post('/admin/login', [AdminController::class, 'login']);
        Route::post('/accounts/login', [AccountsController::class, 'login']);
        // Switch Account — `add` is public (login + return snapshot)
        Route::post('/switch-account/add', [SwitchAccountController::class, 'add']);
    });

    // OTP / password reset: per-email rate limit defined in AppServiceProvider.
    Route::middleware('throttle:otp')->group(function () {
        Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });

    Route::get('/terms-and-conditions', [AuthController::class, 'termsAndConditions']);
    Route::get('/privacy-policy', [AuthController::class, 'privacyPolicy']);
    Route::get('/terms-of-use', [AuthController::class, 'termsOfUse']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('v1')->group(function () {

        //Save Fcm Token (legacy)
        Route::post('/save-fcm-token', [SendNotificationController::class, 'saveFcmToken']);

        //Device token registration for push (app)
        Route::post('/device-token', [SendNotificationController::class, 'registerDeviceToken']);
        Route::post('/device-token/remove', [SendNotificationController::class, 'removeDeviceToken']);

        //Auth Api
        Route::post('/update-password', [AuthController::class, 'updatePassword']);
        Route::get('/school-info', [AuthController::class, 'schoolInfo']);

        //Rules and Regulation
        Route::get('/rules-and-regulation', [AuthController::class, 'rulesAndRegulations']);

        //About App (auth-required)
        Route::get('/about-app', [AuthController::class, 'aboutApp']);

        // User routes here
        Route::prefix('user')->group(function () {
            Route::get('/profile', [UserController::class, 'studentProfile']);

            //Admin Api
            Route::prefix('admin')->group(function () {
                Route::post('/contact', [StudentContactController::class, 'studentAdminContact']);
                Route::get('/contact-list', [StudentContactController::class, 'studentAdminContactList']);
                Route::post('/contact-reply', [StudentContactController::class, 'studentAdminContactReply']);
            });
        });

        // Teacher routes here
        Route::prefix('teacher')->group(function () {
            Route::get('/profile', [TeacherController::class, 'teacherProfile']);
            Route::get('/subject', [SubjectController::class, 'getTeacherSubject']);
            Route::get('/dashboard', [DashboardController::class, 'teacherDashboard']); // home + analytics

            //Admin Api
            Route::prefix('admin')->group(function () {
                Route::post('/contact', [TeacherContactController::class, 'teacherAdminContact']);
                Route::get('/contact-list', [TeacherContactController::class, 'teacherAdminContactList']);
                Route::post('/contact-reply', [TeacherContactController::class, 'teacherAdminContactReply']);
            });
        });

        //Announcement Routes All
        Route::prefix('announcement')->group(function () {
            Route::post('/', [AnnouncementController::class, 'announcementList']);
            Route::get('/{id}', [AnnouncementController::class, 'getAnnouncement']);
        });

        //Library Routes All
        Route::prefix('library')->group(function () {
            Route::post('/', [LibraryController::class, 'libraryList']);
            Route::get('/{id}', [LibraryController::class, 'getLibraryItem']);
        });

        //Subject Routes All
        Route::prefix('subject')->group(function () {
            Route::get('/', [SubjectController::class, 'getAllSubject']);
        });

        //Content Routes All
        Route::prefix('content')->group(function () {
            Route::post('/upload', [ContentController::class, 'saveChapterTopic']);
            Route::post('/get', [ContentController::class, 'getChapterTopics']);
            Route::post('/chapter', [ContentController::class, 'createChapter']);          // create single chapter
            Route::post('/topic', [ContentController::class, 'createTopic']);              // create single topic
            Route::post('/chapter/{chapter_id}', [ContentController::class, 'updateChapterName']);
            Route::delete('/chapter-delete/{chapter_id}', [ContentController::class, 'deleteChapter']);
            Route::post('/topic/{topic_id}', [ContentController::class, 'updateTopic']);
            Route::delete('/topic-delete/{topic_id}', [ContentController::class, 'deleteTopic']);
        });

        //Home Work Routes All
        Route::prefix('homework')->group(function () {
            Route::post('/upload', [HomeWorkController::class, 'uploadHomeWork']);
            Route::post('/get', [HomeWorkController::class, 'allHomeWork']);
            Route::post('/update/{homework_id}', [HomeWorkController::class, 'updateHomeWork']);
            Route::delete('/delete/{homework_id}', [HomeWorkController::class, 'destroyHomeWork']);
            Route::get('/get/{homework_id}', [HomeWorkController::class, 'showSingleHomeWork']);
            Route::post('/student', [HomeWorkController::class, 'studentHomeWork']);
            // Student marks a homework done / not done (body: completed=true|false).
            Route::post('/complete/{homework_id}', [HomeWorkController::class, 'markComplete']);
        });

        //Quiz Routes All
        Route::prefix('quiz')->group(function () {
            Route::post('/upload', [McqController::class, 'uploadQuiz']);
            Route::post('/get', [McqController::class, 'viewAllQuizzes']);
            Route::post('/update/{id}', [McqController::class, 'updateQuiz']);   // teacher
            Route::delete('/delete/{id}', [McqController::class, 'deleteQuiz']); // teacher
            Route::post('/submit-answer', [McqController::class, 'submitAnswer']);
            Route::post('/get/user-answer', [McqController::class, 'getUserAnswers']);
        });

        // Attendance All Routes
        Route::prefix('attendance')->group(function () {
            Route::post('/', [AttendanceController::class, 'bulkSubmitAttendance']);
            Route::post('/mark-holiday', [AttendanceController::class, 'markHoliday']); // teacher marks a class holiday
            Route::post('/get-student-for-attendance', [AttendanceController::class, 'getStudentsForAttendance']);
            Route::post('/summary', [AttendanceController::class, 'getAttendanceSummary']);
            Route::post('/teacher', [AttendanceController::class, 'teacherAttendance']);
            Route::get('/today-teacher', [AttendanceController::class, 'todaysAttendance']);
            Route::get('/my', [AttendanceController::class, 'myAttendance']); // self view — student & teacher
        });

        // Syllabus Routes All
        Route::prefix('syllabus')->group(function () {
            Route::post('/', [SyllabusController::class, 'getSyllabuses']);
            Route::post('/upload', [SyllabusController::class, 'saveSyllabus']);
            Route::post('/update/{id}', [SyllabusController::class, 'updateSyllabus']);
            Route::delete('/delete/{id}', [SyllabusController::class, 'deleteSyllabus']);
            Route::post('/download/{id}', [SyllabusController::class, 'downloadSyllabus']);
        });

        //Filter Apis
        Route::prefix('filter')->group(function () {
            Route::get('/all', [AuthController::class, 'getCompleteCurriculumSimple']);
        });

        // ─── Teacher: Marks + Exam-Copy management ───────────────────────────
        // All endpoints scoped automatically to the (class, section, subject)
        // triples the teacher teaches via the timetable.
        Route::prefix('teacher')->group(function () {

            // Dropdown source — class+section+subject combos the teacher teaches
            Route::get('/classes-subjects', [TeacherMarksController::class, 'classesSubjects']);

            // Marks (text data)
            Route::prefix('marks')->group(function () {
                Route::get('/students',   [TeacherMarksController::class, 'students']); // ?standard_id=&section_id=
                Route::get('/',           [TeacherMarksController::class, 'index']);
                Route::post('/',          [TeacherMarksController::class, 'store']);
                Route::get('/{id}',       [TeacherMarksController::class, 'show'])->whereNumber('id');
                Route::put('/{id}',       [TeacherMarksController::class, 'update'])->whereNumber('id');
                Route::delete('/{id}',    [TeacherMarksController::class, 'destroy'])->whereNumber('id');
            });

            // Exam-copy PDFs
            Route::prefix('exam-copies')->group(function () {
                Route::get('/',           [TeacherExamCopyController::class, 'index']);
                Route::post('/',          [TeacherExamCopyController::class, 'store']);    // multipart
                Route::get('/{id}',       [TeacherExamCopyController::class, 'show'])->whereNumber('id');
                Route::post('/{id}',      [TeacherExamCopyController::class, 'update'])->whereNumber('id'); // multipart, accepts _method=PUT
                Route::put('/{id}',       [TeacherExamCopyController::class, 'update'])->whereNumber('id');
                Route::delete('/{id}',    [TeacherExamCopyController::class, 'destroy'])->whereNumber('id');
            });
        });

        // ─── Student: View own marks + own exam-copy PDFs ────────────────────
        // Read-only, always scoped to the authenticated student.
        Route::prefix('student')->group(function () {

            // Aggregated home-screen + analytics data
            Route::get('/dashboard', [DashboardController::class, 'studentDashboard']);

            Route::prefix('marks')->group(function () {
                Route::get('/',                    [StudentMarksController::class, 'index']);
                Route::get('/overall-performance', [StudentMarksController::class, 'overallPerformance']);
            });

            Route::prefix('exam-copies')->group(function () {
                Route::get('/',     [StudentExamCopyController::class, 'index']);
                Route::get('/{id}', [StudentExamCopyController::class, 'show'])->whereNumber('id');
            });
        });

        //TimeTable Routes All
        Route::prefix('time-table')->group(function () {
            Route::post('/', [TimeTableController::class, 'getTimeTable']);
            Route::get('/student', [TimeTableController::class, 'studentTimeTable']); // student weekly view
        });

        // Calenders Api
        Route::prefix('calendar')->group(function () {
            Route::post('/events', [CalendarController::class, 'getEvents']);
            Route::get('/events/today', [CalendarController::class, 'getTodayEvents']);
            Route::get('/events/{id}', [CalendarController::class, 'getEvent']);
        });

        // Id Card Api
        Route::prefix('id-card')->group(function () {
            Route::get('/student',    [IdCardController::class, 'getStudentIdCard']);
            Route::get('/teacher',    [IdCardController::class, 'getTeacherIdCard']);
            Route::get('/admit-card', [IdCardController::class, 'getStudentAdmitCard']);
        });

        // Books Api
        Route::prefix('books')->group(function () {
            Route::get('/',    [BookController::class, 'index']);
            Route::get('/{id}', [BookController::class, 'show']);
        });

        // Instructors Api
        Route::prefix('instructors')->group(function () {
            Route::get('/',    [InstructorController::class, 'index']);
            Route::get('/{id}', [InstructorController::class, 'show']);
        });

        // Fees Api  (student only)
        Route::prefix('fees')->group(function () {
            Route::get('/summary',   [FeeController::class, 'summary']);
            Route::get('/structure', [FeeController::class, 'structure']);
            Route::get('/payments',  [FeeController::class, 'payments']);

            // Dynamic fee data (student dashboard, academic, transport, penalties)
            Route::get('/dashboard',  [FeeController::class, 'dashboard']);
            Route::get('/academic',   [FeeController::class, 'academic']);
            Route::get('/transport',  [FeeController::class, 'transport']);
            Route::get('/penalties',  [FeeController::class, 'penalties']);

            // Online fee payment (PhonePe) — initiation is rate-limited.
            Route::post('/pay', [PaymentController::class, 'initiate'])->middleware('throttle:payments');
            Route::get('/pay/{merchantOrderId}/status', [PaymentController::class, 'status']);
        });

        // School Admin Api (role: admin / sub-admin) — Phase 0
        Route::prefix('admin')->group(function () {
            Route::get('/me',        [AdminController::class, 'me']);
            Route::get('/dashboard', [AdminController::class, 'dashboard']);
            Route::get('/analytics', [AdminController::class, 'analytics']);

            // More → Admissions / Users / Rate LMS
            Route::get('/admissions', [AdminController::class, 'admissions']);
            Route::get('/users',      [AdminController::class, 'users']);
            Route::get('/rating',     [AdminController::class, 'getRating']);
            Route::post('/rating',    [AdminController::class, 'submitRating']);

            // Performance & Exam Copies (read)
            Route::get('/performance', [AdminController::class, 'performance']);
            Route::get('/exam-copies', [AdminController::class, 'examCopies']);

            // Profile / School Info (mirrors web Components\Profile)
            Route::get('/profile',                   [AdminProfileController::class, 'show']);
            Route::post('/profile/logo',             [AdminProfileController::class, 'updateLogo']);
            Route::put('/profile/school-info',       [AdminProfileController::class, 'updateSchoolInfo']);
            Route::post('/profile/members',          [AdminProfileController::class, 'addMember']);
            Route::post('/profile/members/{id}',     [AdminProfileController::class, 'updateMember'])->whereNumber('id');
            Route::delete('/profile/members/{id}',   [AdminProfileController::class, 'deleteMember'])->whereNumber('id');
            Route::post('/profile/documents',        [AdminProfileController::class, 'addDocument']);
            Route::delete('/profile/documents/{id}', [AdminProfileController::class, 'deleteDocument'])->whereNumber('id');
            Route::post('/profile/password',         [AdminProfileController::class, 'updatePassword']);

            // Announcements (manage)
            Route::get('/announcements',         [AdminContentController::class, 'announcements']);
            Route::post('/announcements',        [AdminContentController::class, 'storeAnnouncement']);
            Route::post('/announcements/{id}',   [AdminContentController::class, 'updateAnnouncement'])->whereNumber('id');
            Route::delete('/announcements/{id}', [AdminContentController::class, 'deleteAnnouncement'])->whereNumber('id');

            // Calendar events (manage — listing/show reuse /calendar/* which is org-scoped)
            Route::post('/calendar/events',        [AdminContentController::class, 'storeEvent']);
            Route::put('/calendar/events/{id}',    [AdminContentController::class, 'updateEvent'])->whereNumber('id');
            Route::delete('/calendar/events/{id}', [AdminContentController::class, 'deleteEvent'])->whereNumber('id');

            // Enquiries (teacher / student)
            Route::get('/enquiries',                    [AdminContentController::class, 'enquiries']);
            Route::post('/enquiries/{tab}/{id}/reply',  [AdminContentController::class, 'replyEnquiry'])->whereNumber('id');
            Route::delete('/enquiries/{tab}/{id}',      [AdminContentController::class, 'deleteEnquiry'])->whereNumber('id');

            // ─── Phase 1: Academic management (web parity) ───────────────────

            // Standards — Classes / Sections / Subjects
            Route::get('/academic-lookups', [AdminStandardController::class, 'lookups']);
            Route::get('/standards',         [AdminStandardController::class, 'standards']);
            Route::post('/standards',        [AdminStandardController::class, 'storeStandard']);
            Route::put('/standards/{id}',    [AdminStandardController::class, 'updateStandard'])->whereNumber('id');
            Route::delete('/standards/{id}', [AdminStandardController::class, 'deleteStandard'])->whereNumber('id');
            Route::get('/sections',          [AdminStandardController::class, 'sections']);
            Route::post('/sections',         [AdminStandardController::class, 'storeSection']);
            Route::put('/sections/{id}',     [AdminStandardController::class, 'updateSection'])->whereNumber('id');
            Route::delete('/sections/{id}',  [AdminStandardController::class, 'deleteSection'])->whereNumber('id');
            Route::get('/subjects',          [AdminStandardController::class, 'subjects']);
            Route::post('/subjects',         [AdminStandardController::class, 'storeSubject']);
            Route::post('/subjects/{id}',    [AdminStandardController::class, 'updateSubject'])->whereNumber('id');
            Route::delete('/subjects/{id}',  [AdminStandardController::class, 'deleteSubject'])->whereNumber('id');

            // Students
            Route::get('/students/lookups',  [AdminStudentController::class, 'lookups']);
            Route::get('/students',          [AdminStudentController::class, 'index']);
            Route::get('/students/{id}',     [AdminStudentController::class, 'show'])->whereNumber('id');
            Route::post('/students',         [AdminStudentController::class, 'store']);
            Route::post('/students/{id}',    [AdminStudentController::class, 'update'])->whereNumber('id');
            Route::delete('/students/{id}',  [AdminStudentController::class, 'destroy'])->whereNumber('id');

            // Teachers
            Route::get('/teachers',          [AdminTeacherController::class, 'index']);
            Route::get('/teachers/{id}',     [AdminTeacherController::class, 'show'])->whereNumber('id');
            Route::post('/teachers',         [AdminTeacherController::class, 'store']);
            Route::post('/teachers/{id}',    [AdminTeacherController::class, 'update'])->whereNumber('id');
            Route::delete('/teachers/{id}',  [AdminTeacherController::class, 'destroy'])->whereNumber('id');

            // ID Cards (student / teacher / employee)
            Route::get('/id-cards',                 [AdminIdCardController::class, 'index']);
            Route::post('/id-cards/generate',       [AdminIdCardController::class, 'generate']);
            Route::get('/id-cards/{type}/{id}',     [AdminIdCardController::class, 'show'])->whereNumber('id');
            Route::put('/id-cards/{type}/{id}',     [AdminIdCardController::class, 'update'])->whereNumber('id');
            Route::delete('/id-cards/{type}/{id}',  [AdminIdCardController::class, 'destroy'])->whereNumber('id');

            // Exams + Syllabus
            Route::get('/exams/syllabus/options', [AdminExamController::class, 'syllabusOptions']);
            Route::get('/exams/syllabus',         [AdminExamController::class, 'syllabus']);
            Route::post('/exams/syllabus',        [AdminExamController::class, 'storeSyllabus']);
            Route::delete('/exams/syllabus',      [AdminExamController::class, 'deleteSyllabus']);
            Route::get('/exams',                  [AdminExamController::class, 'index']);
            Route::post('/exams',                 [AdminExamController::class, 'store']);
            Route::put('/exams/{id}',             [AdminExamController::class, 'update'])->whereNumber('id');
            Route::post('/exams/{id}/toggle-publish', [AdminExamController::class, 'togglePublish'])->whereNumber('id');
            Route::delete('/exams/{id}',          [AdminExamController::class, 'destroy'])->whereNumber('id');

            // ─── Phase 1b: Curriculum modules (web parity) ───────────────────

            // Shared curriculum lookups (used by Syllabus / Content / Quiz)
            Route::get('/curriculum/subjects', [AdminSyllabusController::class, 'subjects']);
            Route::get('/curriculum/chapters', [AdminSyllabusController::class, 'chapters']);

            // Syllabus — chapters & topics
            Route::get('/syllabus/stats',          [AdminSyllabusController::class, 'stats']);
            Route::get('/syllabus',                [AdminSyllabusController::class, 'index']);
            Route::post('/syllabus/chapters',      [AdminSyllabusController::class, 'storeChapters']);
            Route::put('/syllabus/chapters/{id}',  [AdminSyllabusController::class, 'updateChapter'])->whereNumber('id');
            Route::delete('/syllabus/chapters/{id}', [AdminSyllabusController::class, 'deleteChapter'])->whereNumber('id');
            Route::post('/syllabus/topics',        [AdminSyllabusController::class, 'storeTopics']);
            Route::put('/syllabus/topics/{id}',    [AdminSyllabusController::class, 'updateTopic'])->whereNumber('id');
            Route::delete('/syllabus/topics/{id}', [AdminSyllabusController::class, 'deleteTopic'])->whereNumber('id');

            // Content — chapter/topic learning content
            Route::get('/content/stats',          [AdminChapterContentController::class, 'stats']);
            Route::get('/content',                [AdminChapterContentController::class, 'index']);
            Route::post('/content',               [AdminChapterContentController::class, 'save']);
            Route::delete('/content/{type}/{id}', [AdminChapterContentController::class, 'clear'])->whereNumber('id');

            // Quiz — MCQs on chapters/topics
            Route::get('/quiz/stats',        [AdminQuizController::class, 'stats']);
            Route::get('/quiz',              [AdminQuizController::class, 'index']);
            Route::put('/quiz/mcqs',         [AdminQuizController::class, 'update']);
            Route::delete('/quiz/mcqs',      [AdminQuizController::class, 'destroy']);
            Route::get('/quiz/{type}/{id}',  [AdminQuizController::class, 'mcqs'])->whereNumber('id');
            Route::post('/quiz/{type}/{id}', [AdminQuizController::class, 'store'])->whereNumber('id');

            // Books (library)
            Route::get('/books/options', [AdminBookController::class, 'options']);
            Route::get('/books',         [AdminBookController::class, 'index']);
            Route::post('/books',        [AdminBookController::class, 'store']);
            Route::post('/books/{id}',   [AdminBookController::class, 'update'])->whereNumber('id');
            Route::delete('/books/{id}', [AdminBookController::class, 'destroy'])->whereNumber('id');

            // ─── Phase 1c: Timetable + Arrangement (web parity) ──────────────

            // Timetable
            Route::get('/timetable/lookups', [AdminTimetableController::class, 'lookups']);
            Route::get('/timetable/stats',   [AdminTimetableController::class, 'stats']);
            Route::get('/timetable/builder', [AdminTimetableController::class, 'builder']);
            Route::get('/timetable',         [AdminTimetableController::class, 'index']);
            Route::post('/timetable',        [AdminTimetableController::class, 'save']);
            Route::delete('/timetable',      [AdminTimetableController::class, 'destroy']);

            // Arrangement (substitutions)
            Route::get('/arrangement',         [AdminArrangementController::class, 'index']);
            Route::post('/arrangement',        [AdminArrangementController::class, 'assign']);
            Route::delete('/arrangement/{id}', [AdminArrangementController::class, 'destroy'])->whereNumber('id');

            // ─── Homework (web parity) ───────────────────────────────────────
            Route::get('/homework/lookups',  [AdminHomeworkController::class, 'lookups']);
            Route::get('/homework/subjects', [AdminHomeworkController::class, 'subjects']);
            Route::get('/homework/stats',    [AdminHomeworkController::class, 'stats']);
            Route::get('/homework/status',   [AdminHomeworkController::class, 'status']);
            Route::get('/homework',          [AdminHomeworkController::class, 'index']);
            Route::post('/homework',         [AdminHomeworkController::class, 'store']);
            Route::post('/homework/{id}',    [AdminHomeworkController::class, 'update'])->whereNumber('id');
            Route::delete('/homework/{id}',  [AdminHomeworkController::class, 'destroy'])->whereNumber('id');

            // ─── Attendance (web parity) ─────────────────────────────────────
            Route::get('/attendance/lookups',            [AdminAttendanceController::class, 'lookups']);
            Route::get('/attendance/students',           [AdminAttendanceController::class, 'students']);
            // Teacher
            Route::get('/attendance/teacher/mark',       [AdminAttendanceController::class, 'teacherMarkList']);
            Route::post('/attendance/teacher/mark',      [AdminAttendanceController::class, 'submitTeacherAttendance']);
            Route::get('/attendance/teacher/by-date',    [AdminAttendanceController::class, 'teacherByDate']);
            Route::get('/attendance/teacher/calendar',   [AdminAttendanceController::class, 'teacherCalendar']);
            // Student
            Route::get('/attendance/student/mark',       [AdminAttendanceController::class, 'studentMarkList']);
            Route::post('/attendance/student/mark',      [AdminAttendanceController::class, 'submitStudentAttendance']);
            Route::get('/attendance/student/by-date',    [AdminAttendanceController::class, 'studentByDate']);
            Route::get('/attendance/student/calendar',   [AdminAttendanceController::class, 'studentCalendar']);
            // Class teachers
            Route::get('/attendance/class-teachers',       [AdminAttendanceController::class, 'classTeachers']);
            Route::post('/attendance/class-teachers',      [AdminAttendanceController::class, 'saveClassTeacher']);
            Route::delete('/attendance/class-teachers/{id}', [AdminAttendanceController::class, 'deleteClassTeacher'])->whereNumber('id');

            // ─── Transport (web parity) ──────────────────────────────────────
            Route::get('/transport/stats',          [AdminTransportController::class, 'stats']);
            Route::get('/transport/route-options',  [AdminTransportController::class, 'routeOptions']);
            // Routes
            Route::get('/transport/routes',              [AdminTransportController::class, 'routes']);
            Route::post('/transport/routes',             [AdminTransportController::class, 'saveRoute']);
            Route::post('/transport/routes/{id}',        [AdminTransportController::class, 'saveRoute'])->whereNumber('id');
            Route::post('/transport/routes/{id}/toggle', [AdminTransportController::class, 'toggleRoute'])->whereNumber('id');
            Route::delete('/transport/routes/{id}',      [AdminTransportController::class, 'deleteRoute'])->whereNumber('id');
            // Drivers
            Route::get('/transport/drivers',              [AdminTransportController::class, 'drivers']);
            Route::post('/transport/drivers',             [AdminTransportController::class, 'saveDriver']);
            Route::post('/transport/drivers/{id}',        [AdminTransportController::class, 'saveDriver'])->whereNumber('id');
            Route::post('/transport/drivers/{id}/toggle', [AdminTransportController::class, 'toggleDriver'])->whereNumber('id');
            Route::delete('/transport/drivers/{id}',      [AdminTransportController::class, 'deleteDriver'])->whereNumber('id');
            // Transport students (per-month billing)
            Route::get('/transport/students',        [AdminTransportController::class, 'students']);
            Route::post('/transport/students/months',[AdminTransportController::class, 'saveStudentMonths']);
            Route::delete('/transport/students',     [AdminTransportController::class, 'removeStudent']);
            // Fees
            Route::get('/transport/fees/students',       [AdminTransportController::class, 'feeStudents']);
            Route::get('/transport/fees/summary',        [AdminTransportController::class, 'feeSummary']);
            Route::post('/transport/fees/payment',       [AdminTransportController::class, 'recordPayment']);
            Route::delete('/transport/fees/payment/{id}',[AdminTransportController::class, 'deletePayment'])->whereNumber('id');

            // ─── Credit (web parity) ─────────────────────────────────────────
            Route::get('/credit/stats',        [AdminCreditController::class, 'stats']);
            Route::post('/credit/suggest-end', [AdminCreditController::class, 'suggestEndDate']);
            Route::get('/credit',              [AdminCreditController::class, 'index']);
            Route::get('/credit/{id}',         [AdminCreditController::class, 'show'])->whereNumber('id');
            Route::post('/credit',             [AdminCreditController::class, 'store']);
            Route::post('/credit/{id}',        [AdminCreditController::class, 'update'])->whereNumber('id');
            Route::delete('/credit/{id}',      [AdminCreditController::class, 'destroy'])->whereNumber('id');

            // ─── Admit Card (web parity) ─────────────────────────────────────
            Route::get('/admit-card/lookups',   [AdminAdmitCardController::class, 'lookups']);
            Route::get('/admit-card/analytics', [AdminAdmitCardController::class, 'analytics']);
            Route::get('/admit-card',           [AdminAdmitCardController::class, 'index']);
            Route::post('/admit-card/issue',    [AdminAdmitCardController::class, 'issueOne']);
            Route::post('/admit-card/generate', [AdminAdmitCardController::class, 'generate']);
            Route::get('/admit-card/{id}',      [AdminAdmitCardController::class, 'show'])->whereNumber('id');
            Route::get('/admit-card/{id}/pdf',  [AdminAdmitCardController::class, 'pdf'])->whereNumber('id');
            Route::delete('/admit-card/{id}',   [AdminAdmitCardController::class, 'destroy'])->whereNumber('id');

            // ─── Report Card (web parity) ────────────────────────────────────
            Route::get('/report-card/lookups',        [AdminReportCardController::class, 'lookups']);
            Route::get('/report-card/stats',          [AdminReportCardController::class, 'stats']);
            Route::get('/report-card/issue-students', [AdminReportCardController::class, 'issueStudents']);
            Route::post('/report-card/issue',         [AdminReportCardController::class, 'issue']);
            Route::get('/report-card',                [AdminReportCardController::class, 'index']);
            Route::get('/report-card/{id}/pdf',       [AdminReportCardController::class, 'pdf'])->whereNumber('id');
            Route::post('/report-card/{id}/revoke',   [AdminReportCardController::class, 'revoke'])->whereNumber('id');

            // ─── TC & Certificate (web parity) ───────────────────────────────
            Route::get('/tc-certificate/lookups',  [AdminTcCertificateController::class, 'lookups']);
            Route::get('/tc-certificate/stats',    [AdminTcCertificateController::class, 'stats']);
            Route::get('/tc-certificate/students', [AdminTcCertificateController::class, 'students']);
            Route::get('/tc-certificate',          [AdminTcCertificateController::class, 'index']);
            // Certificates (achievement / participation)
            Route::post('/tc-certificate/cert',          [AdminTcCertificateController::class, 'storeCert']);
            Route::get('/tc-certificate/cert/{id}/pdf',  [AdminTcCertificateController::class, 'certPdf'])->whereNumber('id');
            Route::post('/tc-certificate/cert/{id}',     [AdminTcCertificateController::class, 'updateCert'])->whereNumber('id');
            Route::delete('/tc-certificate/cert/{id}',   [AdminTcCertificateController::class, 'destroyCert'])->whereNumber('id');
            // Transfer certificates
            Route::post('/tc-certificate/tc',            [AdminTcCertificateController::class, 'storeTc']);
            Route::get('/tc-certificate/tc/{id}/pdf',    [AdminTcCertificateController::class, 'tcPdf'])->whereNumber('id');
            Route::post('/tc-certificate/tc/{id}',       [AdminTcCertificateController::class, 'updateTc'])->whereNumber('id');
            Route::delete('/tc-certificate/tc/{id}',     [AdminTcCertificateController::class, 'destroyTc'])->whereNumber('id');
        });

        // Accounts Api (role: accounts) — Phase 0
        Route::prefix('accounts')->group(function () {
            Route::get('/me',        [AccountsController::class, 'me']);
            Route::get('/dashboard', [AccountsController::class, 'dashboard']);
        });

        // Transport Api
        Route::prefix('transport')->group(function () {
            Route::get('/my-route', [TransportController::class, 'myRoute']);
            Route::get('/routes',   [TransportController::class, 'routes']);
        });

        // Exams Api
        Route::prefix('exams')->group(function () {
            Route::get('/',                    [ExamController::class, 'index']);
            Route::get('/{id}',                [ExamController::class, 'show'])->whereNumber('id');
            Route::get('/{id}/syllabus',       [ExamController::class, 'syllabus'])->whereNumber('id');
            // Student admit card for a specific exam (issued check + streamed PDF)
            Route::get('/{id}/admit-card',     [ExamController::class, 'admitCard'])->whereNumber('id');
            Route::get('/{id}/admit-card/pdf', [ExamController::class, 'admitCardPdf'])->whereNumber('id');
        });

        // Filters Api (cascading dropdowns for app UI)
        Route::prefix('filters')->group(function () {
            Route::get('/classes',  [FilterController::class, 'classes']);
            Route::get('/sections', [FilterController::class, 'sections']);
            Route::get('/subjects', [FilterController::class, 'subjects']);
            Route::get('/exams',    [FilterController::class, 'exams']);
        });

        // Seating Plan Api  (student only)
        Route::prefix('seating-plan')->group(function () {
            Route::get('/',    [SeatingPlanController::class, 'mySeating']);
            Route::get('/all', [SeatingPlanController::class, 'all']);
        });

        // Report Card Api  (student only)
        Route::prefix('report-card')->group(function () {
            Route::get('/',         [ReportCardController::class, 'index']);
            Route::get('/{id}',     [ReportCardController::class, 'show'])->whereNumber('id');
            Route::get('/{id}/pdf', [ReportCardController::class, 'pdf'])->whereNumber('id');
        });

        // Switch Account Api
        Route::prefix('switch-account')->group(function () {
            Route::get('/me',      [SwitchAccountController::class, 'me']);
            Route::post('/remove', [SwitchAccountController::class, 'remove']);
            Route::get('/schools', [SwitchAccountController::class, 'schools']);
            Route::post('/switch', [SwitchAccountController::class, 'switch']);
        });
    });


    Route::post('/notifications/send-to-me', [SendNotificationController::class, 'sendToMe']);
});

// PhonePe server-to-server webhook (public; verified by Authorization header).
Route::post('/v1/phonepe/webhook', [PhonePeController::class, 'handleWebhook']);

Route::get('/admit-card/verify/{admitCardNumber}', [IdCardController::class, 'verifyAdmitCard'])->name('admit-card.verify');
