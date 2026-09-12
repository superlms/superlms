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
            'label'  => 'Student records — personal details, class, section, photo, admission number.',
            'search' => ['full_name', 'admission_no', 'roll_no', 'father_name', 'mother_name', 'phone', 'email'],
        ],
        'teachers' => [
            'model'  => \App\Models\Teacher\TeacherDetail::class,
            'label'  => 'Teacher records. The teacher\'s name and email live on their login (user_id).',
            'search' => ['employee_id', 'phone', 'qualification'],
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
            'model'  => \App\Models\Admin\SchoolDocument::class,
            'label'  => 'Documents uploaded for the school.',
            'search' => ['title', 'type'],
        ],
        'school_users' => [
            'model'  => \App\Models\User::class,
            'label'  => 'Login accounts belonging to this school — admins, accounts, teachers, students.',
            'search' => ['name', 'email', 'mobile_number', 'role'],
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
