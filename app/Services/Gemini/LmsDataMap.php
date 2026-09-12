<?php

namespace App\Services\Gemini;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * The registry behind the assistant's generic read tools.
 *
 * The hand-written tools in {@see LmsToolbox} answer the questions people
 * actually ask ("who has pending fees", "top 3 of nursery"). This map is what
 * catches everything else: it names the records the assistant may read and
 * lets it discover their columns, so a question about a field nobody thought
 * to expose — a profile photo, a religion, a route number — is answerable
 * instead of being met with "that is not available in this panel".
 *
 * Two rules keep it safe:
 *  1. Only entities listed here can be reached, and only through the query
 *     tools, which pin every read to the caller's organization.
 *  2. Columns are discovered from the real table, then filtered through
 *     {@see SENSITIVE} — passwords, tokens, OTPs and secrets can never be
 *     named, selected, filtered on or returned, whatever the model asks for.
 */
class LmsDataMap
{
    /**
     * Column names that never leave the database, matched as substrings so a
     * new `*_token` column is excluded the day it is added.
     */
    private const SENSITIVE = [
        'password', 'token', 'secret', 'otp', 'signature', 'api_key', 'private_key',
        'remember', 'two_factor', 'webhook', 'credential',
    ];

    /** Records every panel may read, pinned to its own school. */
    public const SCHOOL = [
        'students' => [
            'model'  => \App\Models\Student\StudentDetail::class,
            'label'  => 'Student records — personal details, class, section, admission number. The PROFILE PHOTO the panel shows is on the login, as user.image, not on this row.',
            'search' => ['full_name', 'admission_no', 'roll_no', 'father_name', 'mother_name', 'phone', 'email'],
            'related' => [
                'user' => [
                    'column' => 'user_id',
                    'model'  => \App\Models\User::class,
                    'fields' => ['name', 'email', 'mobile_number', 'image', 'is_active', 'last_login_at'],
                ],
            ],
            // student_details.image is vestigial — the panel uploads the photo
            // to the login row — so a question about a photo has to land on
            // user.image however it is spelled.
            'aliases' => [
                'image'         => 'user.image',
                'photo'         => 'user.image',
                'profile_image' => 'user.image',
                'profile_photo' => 'user.image',
                'picture'       => 'user.image',
                'name'          => 'full_name',
                'student_name'  => 'full_name',
            ],
        ],
        'teachers' => [
            'model'  => \App\Models\Teacher\TeacherDetail::class,
            'label'  => 'Teacher records. Name, email and photo live on the login, as user.name / user.image.',
            'search' => ['employee_id', 'phone', 'qualification'],
            'related' => [
                'user' => [
                    'column' => 'user_id',
                    'model'  => \App\Models\User::class,
                    'fields' => ['name', 'email', 'mobile_number', 'image', 'is_active', 'last_login_at'],
                ],
            ],
            'aliases' => [
                'image'         => 'user.image',
                'photo'         => 'user.image',
                'profile_image' => 'user.image',
                'picture'       => 'user.image',
                'name'          => 'user.name',
                'teacher_name'  => 'user.name',
                'email'         => 'user.email',
            ],
        ],
        'employees' => [
            'model'  => \App\Models\Admin\AdminEmployee::class,
            'label'  => 'Non-teaching staff — name, designation, salary, joining date.',
            'search' => ['name', 'email', 'mobile', 'designation'],
        ],
        'classes' => [
            'model'  => \App\Models\Student\Standard::class,
            'label'  => 'Classes (standards).',
            'search' => ['name'],
        ],
        'sections' => [
            'model'  => \App\Models\Student\Section::class,
            'label'  => 'Sections inside a class.',
            'search' => ['name'],
        ],
        'subjects' => [
            'model'  => \App\Models\Student\Subject::class,
            'label'  => 'Subjects taught.',
            'search' => ['name', 'code'],
        ],
        'student_attendance' => [
            'model'  => \App\Models\Student\StudentAttendance::class,
            'label'  => 'Daily student attendance rows. status: 1 present, 0 absent, 2 half day, 3 holiday.',
            'search' => [],
        ],
        'teacher_attendance' => [
            'model'  => \App\Models\Teacher\TeacherAttendance::class,
            'label'  => 'Daily teacher attendance. status: 1 present, 0 absent, 2 half day, 3 holiday.',
            'search' => [],
        ],
        'employee_attendance' => [
            'model'  => \App\Models\Admin\AdminAttendance::class,
            'label'  => 'Daily non-teaching staff attendance.',
            'search' => [],
        ],
        'exams' => [
            'model'  => \App\Models\Admin\Exam::class,
            'label'  => 'Exams that have been set up.',
            'search' => ['exam_name', 'term', 'exam_type'],
        ],
        'exam_marks' => [
            'model'  => \App\Models\Admin\ExamCopy::class,
            'label'  => 'One row per student per subject per exam. Prefer the exam_results tool for ranking.',
            'search' => ['grade', 'remarks'],
        ],
        'exam_datesheets' => [
            'model'  => \App\Models\Admin\ExamDatesheet::class,
            'label'  => 'Datesheet headers; the papers hang off exam_datesheet_id.',
            'search' => [],
        ],
        'report_cards' => [
            'model'  => \App\Models\Admin\ReportCard::class,
            'label'  => 'Generated report cards.',
            'search' => [],
        ],
        'fee_structures' => [
            'model'  => \App\Models\Admin\Fee\FeeStructure::class,
            'label'  => 'What each class is charged.',
            'search' => ['fee_name', 'fee_type'],
        ],
        'fee_payments' => [
            'model'  => \App\Models\Admin\Fee\FeePayment::class,
            'label'  => 'Fee actually collected from students.',
            'search' => ['receipt_no', 'payment_mode', 'fee_type', 'remarks'],
        ],
        'fee_concessions' => [
            'model'  => \App\Models\Admin\Fee\FeeConcession::class,
            'label'  => 'Fee concessions granted to students.',
            'search' => ['reason'],
        ],
        'fee_cycles' => [
            'model'  => \App\Models\Admin\Fee\FeeCycle::class,
            'label'  => 'The school\'s installment cycle.',
            'search' => ['name'],
        ],
        'ledger' => [
            'model'  => \App\Models\Admin\LedgerTransaction::class,
            'label'  => 'School ledger: credit (money in) and expense (money out).',
            'search' => ['party', 'party_to', 'reason', 'mode'],
        ],
        'salary_payments' => [
            'model'  => \App\Models\Admin\AdminSalaryPayment::class,
            'label'  => 'Staff salary payments.',
            'search' => ['month', 'status', 'payment_mode', 'remark'],
        ],
        'transport_routes' => [
            'model'  => \App\Models\Admin\Transportation::class,
            'label'  => 'Bus routes and vehicles.',
            'search' => ['route_name', 'vehicle_number', 'driver_name', 'driver_phone'],
        ],
        'transport_students' => [
            'model'  => \App\Models\Admin\TransportationStudent::class,
            'label'  => 'Which student rides which route.',
            'search' => ['pickup_point'],
        ],
        'transport_fee_payments' => [
            'model'  => \App\Models\Admin\TransportFeePayment::class,
            'label'  => 'Transport fee collected.',
            'search' => ['payment_mode', 'remarks'],
        ],
        'homework' => [
            'model'  => \App\Models\Admin\HomeWork::class,
            'label'  => 'Homework set for classes.',
            'search' => ['title', 'description'],
        ],
        'assignments' => [
            'model'  => \App\Models\Admin\Assignment\Assignment::class,
            'label'  => 'Online assignments.',
            'search' => ['title', 'description'],
        ],
        'announcements' => [
            'model'  => \App\Models\Admin\Announcement::class,
            'label'  => 'Announcements and notices.',
            'search' => ['title', 'description'],
        ],
        'books' => [
            'model'  => \App\Models\Admin\Book::class,
            'label'  => 'Library books.',
            'search' => ['title', 'author', 'isbn'],
        ],
        'certificates' => [
            'model'  => \App\Models\Admin\Certificate::class,
            'label'  => 'Achievement and participation certificates.',
            'search' => ['event_name', 'issued_by', 'certificate_no'],
        ],
        'transfer_certificates' => [
            'model'  => \App\Models\Admin\TransferCertificate::class,
            'label'  => 'Transfer certificates issued.',
            'search' => ['tc_no', 'reason_for_leaving', 'general_conduct'],
        ],
        'admission_enquiries' => [
            'model'  => \App\Models\Admin\AdmissionEnquiry::class,
            'label'  => 'Admission enquiries received.',
            'search' => ['name', 'phone', 'email', 'status'],
        ],
        'timetable' => [
            'model'  => \App\Models\Admin\TeacherTimeTable::class,
            'label'  => 'Timetable periods. day_of_week: 1 Monday … 6 Saturday.',
            'search' => [],
        ],
        'student_id_cards' => [
            'model'  => \App\Models\Admin\StudentIdCard::class,
            'label'  => 'Generated student ID cards.',
            'search' => [],
        ],
        'school_documents' => [
            'model'   => \App\Models\Admin\SchoolDocument::class,
            'label'   => 'Documents uploaded for the school.',
            'search'  => ['title', 'file_type'],
            // No organization_id of its own — it hangs off the school profile.
            'pin_via' => ['column' => 'school_info_id', 'model' => \App\Models\Admin\SchoolInfo::class],
        ],
        'logins' => [
            'model'  => \App\Models\User::class,
            'label'  => 'Login accounts of this school — admins, accounts staff, teachers and students. Carries each person\'s name, email, mobile and profile photo (image).',
            'search' => ['name', 'email', 'mobile_number', 'role'],
        ],
        'staff_profiles' => [
            'model'  => \App\Models\Admin\SchoolUser::class,
            'label'  => 'Profile rows for a school\'s own users — designation, department, employee id, photo.',
            'search' => ['employee_id', 'designation', 'department', 'phone'],
        ],
        'admit_cards' => [
            'model'  => \App\Models\Student\AdmitCard::class,
            'label'  => 'Admit cards generated for exams.',
            'search' => ['admit_card_number', 'student_name', 'father_name'],
        ],
        'teacher_id_cards' => [
            'model'  => \App\Models\Admin\TeacherIdCard::class,
            'label'  => 'Generated teacher ID cards.',
            'search' => [],
        ],
        'employee_id_cards' => [
            'model'  => \App\Models\Admin\EmployeeIdCard::class,
            'label'  => 'Generated employee ID cards.',
            'search' => [],
        ],
        'subject_marks' => [
            'model'  => \App\Models\Admin\ExamSubjectMark::class,
            'label'  => 'Subject-wise marks attached to an exam copy.',
            'search' => ['grade', 'evaluation_type'],
        ],
        'exam_papers' => [
            'model'  => \App\Models\Admin\ExamPaper::class,
            'label'  => 'Question papers uploaded for exams.',
            'search' => [],
        ],
        'syllabus_chapters' => [
            'model'  => \App\Models\Admin\ExamSyllabusChapter::class,
            'label'  => 'Chapters included in an exam\'s syllabus.',
            'search' => [],
        ],
        'chapters' => [
            'model'  => \App\Models\Student\Chapter::class,
            'label'  => 'Chapters of a subject.',
            'search' => ['name', 'description'],
        ],
        'topics' => [
            'model'  => \App\Models\Student\Topic::class,
            'label'  => 'Topics inside a chapter.',
            'search' => ['name', 'description'],
        ],
        'student_syllabus' => [
            'model'  => \App\Models\Student\StudentSyllabus::class,
            'label'  => 'Syllabus progress recorded for students.',
            'search' => [],
        ],
        'class_subjects' => [
            'model'  => \App\Models\Student\StandardSubject::class,
            'label'  => 'Which subjects a class studies.',
            'search' => [],
        ],
        'section_subjects' => [
            'model'  => \App\Models\Student\SectionSubject::class,
            'label'  => 'Which subjects a section studies.',
            'search' => [],
        ],
        'teacher_subjects' => [
            'model'  => \App\Models\Teacher\TeacherSubject::class,
            'label'  => 'Subjects assigned to teachers.',
            'search' => [],
        ],
        'teacher_sections' => [
            'model'  => \App\Models\Teacher\TeacherSection::class,
            'label'  => 'Sections assigned to teachers.',
            'search' => [],
        ],
        'teacher_class_assignments' => [
            'model'  => \App\Models\Teacher\AssignTeacherStandard::class,
            'label'  => 'Class-teacher assignments.',
            'search' => [],
        ],
        'teacher_arrangements' => [
            'model'  => \App\Models\Admin\TeacherArrangement::class,
            'label'  => 'Substitute (arrangement) duties for absent teachers.',
            'search' => ['reason', 'status'],
        ],
        'teacher_availability' => [
            'model'  => \App\Models\Admin\TeacherAvailability::class,
            'label'  => 'When teachers are free or busy.',
            'search' => [],
        ],
        'homework_completions' => [
            'model'  => \App\Models\Admin\HomeWorkCompletion::class,
            'label'  => 'Which students completed which homework.',
            'search' => [],
        ],
        'seating_plans' => [
            'model'  => \App\Models\Admin\Seating\SeatingPlan::class,
            'label'  => 'Exam seating plans.',
            'search' => ['name', 'status'],
        ],
        'seating_rooms' => [
            'model'  => \App\Models\Admin\Seating\SeatingRoom::class,
            'label'  => 'Rooms available for exam seating.',
            'search' => ['name', 'code'],
        ],
        'seat_assignments' => [
            'model'   => \App\Models\Admin\Seating\SeatAssignment::class,
            'label'   => 'Which student sits where in an exam.',
            'search'  => ['class_label'],
            'pin_via' => ['column' => 'seating_plan_id', 'model' => \App\Models\Admin\Seating\SeatingPlan::class],
        ],
        'invigilators' => [
            'model'  => \App\Models\Admin\Seating\SeatingInvigilator::class,
            'label'  => 'Invigilators available for exam duty.',
            'search' => [],
        ],
        'drivers' => [
            'model'  => \App\Models\Admin\DriverDetail::class,
            'label'  => 'Bus drivers — licence, phone, vehicle.',
            'search' => ['name', 'phone', 'licence_number', 'license_number'],
        ],
        'calendar_events' => [
            'model'  => \App\Models\Calendar\TimeTable::class,
            'label'  => 'Calendar events — meetings, holidays, activities.',
            'search' => ['title', 'description', 'type'],
        ],
        'school_info' => [
            'model'  => \App\Models\Admin\SchoolInfo::class,
            'label'  => 'The school\'s own profile: address, contacts, website details.',
            'search' => ['school_name', 'school_email', 'school_mobile'],
        ],
        'management_team' => [
            'model'   => \App\Models\Admin\SchoolManagementTeam::class,
            'label'   => 'Management team shown on the school website.',
            'search'  => ['name', 'designation'],
            'pin_via' => ['column' => 'school_info_id', 'model' => \App\Models\Admin\SchoolInfo::class],
        ],
        'rules' => [
            'model'  => \App\Models\Admin\RulesAndRegulation::class,
            'label'  => 'School rules and regulations.',
            'search' => ['title', 'description'],
        ],
        'fee_settings' => [
            'model'  => \App\Models\Admin\Fee\FeeSettings::class,
            'label'  => 'Fee module settings — late fee rules and the like.',
            'search' => [],
        ],
        'contact_messages_students' => [
            'model'  => \App\Models\Admin\ContactAdminStudent::class,
            'label'  => 'Messages students sent the school office.',
            'search' => ['subject', 'message', 'status'],
        ],
        'contact_messages_teachers' => [
            'model'  => \App\Models\Admin\ContactAdminTeacher::class,
            'label'  => 'Messages teachers sent the school office.',
            'search' => ['subject', 'message', 'status'],
        ],
        'school_enquiries' => [
            'model'  => \App\Models\Admin\AdminEnquiry::class,
            'label'  => 'General enquiries received by the school.',
            'search' => ['name', 'email', 'phone', 'message', 'status'],
        ],
    ];

    /** Records only the platform (super-admin) panel may read. */
    public const PLATFORM = [
        'schools' => [
            'model'  => \App\Models\Organization::class,
            'label'  => 'Schools on the platform.',
            'search' => ['name', 'serial_number', 'email', 'city', 'state', 'education_board'],
        ],
        'platform_fee_payments' => [
            'model'  => \App\Models\SuperAdmin\SuperAdminFeePayment::class,
            'label'  => 'Platform fees paid by schools to SuperLMS.',
            'search' => ['payment_mode', 'transaction_id', 'status'],
        ],
        'platform_fee_structures' => [
            'model'  => \App\Models\SuperAdmin\SuperAdminFeeStructure::class,
            'label'  => 'What SuperLMS charges its schools.',
            'search' => ['name', 'type'],
        ],
        'platform_employees' => [
            'model'  => \App\Models\SuperAdmin\SuperAdminEmployee::class,
            'label'  => 'SuperLMS\'s own staff.',
            'search' => ['name', 'email', 'mobile', 'designation'],
        ],
        'credit_queries' => [
            'model'  => \App\Models\SuperAdmin\CreditQuery::class,
            'label'  => 'Credit requests raised by schools.',
            'search' => ['status', 'message'],
        ],
        'support_messages' => [
            'model'  => \App\Models\Admin\ContactSuperAdmin::class,
            'label'  => 'Support messages schools sent to SuperLMS.',
            'search' => ['subject', 'message', 'status'],
        ],
        'ratings' => [
            'model'  => \App\Models\Admin\RateLms::class,
            'label'  => 'Ratings and feedback schools left about the LMS.',
            'search' => ['feedback'],
        ],
        'demo_requests' => [
            'model'  => \App\Models\WebsiteDemo::class,
            'label'  => 'Demo requests from the marketing site.',
            'search' => ['name', 'email', 'phone', 'school_name', 'status'],
        ],
        'website_contacts' => [
            'model'  => \App\Models\WebsiteContact::class,
            'label'  => 'Contact-form messages from the marketing site.',
            'search' => ['name', 'email', 'phone', 'message'],
        ],
        'payment_transactions' => [
            'model'  => \App\Models\PaymentTransaction::class,
            'label'  => 'Online payment transactions.',
            'search' => ['status', 'merchant_order_id', 'transaction_id'],
        ],
    ];

    /**
     * FK columns worth turning back into names in a result row, so the model
     * never has to report a bare id at anybody.
     *
     * @var array<string,array{model:class-string<Model>,column:string}>
     */
    public const LABELS = [
        'standard_id'       => ['model' => \App\Models\Student\Standard::class,     'column' => 'name'],
        'section_id'        => ['model' => \App\Models\Student\Section::class,      'column' => 'name'],
        'subject_id'        => ['model' => \App\Models\Student\Subject::class,      'column' => 'name'],
        'student_detail_id' => ['model' => \App\Models\Student\StudentDetail::class, 'column' => 'full_name'],
        'exam_id'           => ['model' => \App\Models\Admin\Exam::class,           'column' => 'exam_name'],
        'organization_id'   => ['model' => \App\Models\Organization::class,         'column' => 'name'],
        'user_id'           => ['model' => \App\Models\User::class,                 'column' => 'name'],
        'admin_employee_id' => ['model' => \App\Models\Admin\AdminEmployee::class,  'column' => 'name'],
        'transportation_id' => ['model' => \App\Models\Admin\Transportation::class, 'column' => 'route_name'],
    ];

    /** @return array<string,array<string,mixed>> */
    public static function forScope(LmsScope $scope): array
    {
        return $scope->isPlatform()
            ? array_merge(self::SCHOOL, self::PLATFORM)
            : self::SCHOOL;
    }

    /** @return array<string,mixed>|null */
    public static function entity(LmsScope $scope, string $key): ?array
    {
        return self::forScope($scope)[$key] ?? null;
    }

    /**
     * The columns of an entity that may be read — the real table's columns,
     * minus anything that looks like a secret.
     *
     * @return array<int,string>
     */
    public static function columns(string $modelClass): array
    {
        static $cache = [];

        if (isset($cache[$modelClass])) {
            return $cache[$modelClass];
        }

        /** @var Model $model */
        $model = new $modelClass;

        try {
            $columns = Schema::getColumnListing($model->getTable());
        } catch (\Throwable) {
            $columns = [];
        }

        return $cache[$modelClass] = array_values(array_filter(
            $columns,
            fn (string $column) => ! self::isSensitive($column),
        ));
    }

    public static function isSensitive(string $column): bool
    {
        $column = strtolower($column);

        foreach (self::SENSITIVE as $needle) {
            if (str_contains($column, $needle)) {
                return true;
            }
        }

        return false;
    }
}
