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
 * Three rules keep it safe:
 *  1. Only entities listed here can be reached, and only through the query
 *     tools, which pin every read to the caller's organization.
 *  2. Every entity belongs to a MODULE ({@see MODULE_OF}), and an entity whose
 *     module this login was not granted is not even listed — a sub-admin
 *     without the Fee screen cannot see that fee records exist.
 *  3. Columns are discovered from the real table, then filtered through
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
            'label'  => 'Fee actually collected from students. receipt_number is the receipt, payment_date the day it was taken, waiver_amount a concession applied on the spot and penalty_amount a late fee.',
            'search' => ['receipt_number', 'payment_mode', 'fee_type', 'remark'],
        ],
        'fee_concessions' => [
            'model'  => \App\Models\Admin\Fee\FeeConcession::class,
            'label'  => 'Fee concessions granted to students.',
            'search' => ['reason'],
        ],
        'fee_cycles' => [
            'model'  => \App\Models\Admin\Fee\FeeCycle::class,
            'label'  => 'The school\'s installment cycle: each row is one installment as a PERCENT of the year\'s fee (fee_percent) with a due_date and a penalty_per_day.',
            'search' => ['fee_type', 'academic_year'],
        ],
        'ledger' => [
            'model'  => \App\Models\Admin\LedgerTransaction::class,
            'label'  => 'School ledger: type "credit" is money in, "expense" is money out. txn_date is the day, party who it was with, reason what for.',
            'search' => ['party', 'reason', 'type'],
        ],
        'salary_payments' => [
            'model'  => \App\Models\Admin\AdminSalaryPayment::class,
            'label'  => 'Staff salary payments.',
            'search' => ['month', 'status', 'payment_mode', 'remark'],
        ],
        'transport_routes' => [
            'model'  => \App\Models\Admin\Transportation::class,
            'label'  => 'Bus routes. The driver is a separate record, joined by driver_detail_id — read names, phone and vehicle from the drivers entity.',
            'search' => ['route_name', 'pickup_location', 'drop_location', 'stops'],
        ],
        'transport_students' => [
            'model'  => \App\Models\Admin\TransportationStudent::class,
            'label'  => 'Which student rides which route, and for how many months they are billed.',
            'search' => [],
        ],
        'transport_fee_payments' => [
            'model'  => \App\Models\Admin\TransportFeePayment::class,
            'label'  => 'Transport fee collected, with its own receipt_number.',
            'search' => ['payment_mode', 'remark', 'receipt_number'],
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
            'label'  => 'Announcements and notices. announcement_name is the title, announcement_content the body.',
            'search' => ['announcement_name', 'announcement_content', 'type'],
        ],
        'books' => [
            'model'  => \App\Models\Admin\Book::class,
            'label'  => 'Library books, attached to a class/section/subject.',
            'search' => ['title'],
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
            'label'  => 'Admission enquiries received. student_name is the child, guardian_name the parent, mobile the contact, status where the enquiry has got to, and admission_fee / collected_amount any money already taken.',
            'search' => ['student_name', 'guardian_name', 'mobile', 'email', 'status', 'remarks'],
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
            'label'  => 'Topics inside a chapter. topic_name is the title, topic_content the body.',
            'search' => ['topic_name', 'topic_content'],
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
            'label'  => 'Substitute (arrangement) duties for absent teachers: original_teacher_id is who is away, substitute_teacher_id who covers, on `date`.',
            'search' => ['reason'],
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
            'label'  => 'Rooms available for exam seating — room_name, building, rows x columns and capacity.',
            'search' => ['room_name', 'building', 'notes'],
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
            'label'  => 'Bus drivers. license_no is the licence, vehicle_no the bus. The driver\'s NAME and photo are on the login, as user.name / user.image.',
            'search' => ['license_no', 'vehicle_no', 'vehicle_type', 'phone'],
            'related' => [
                'user' => [
                    'column' => 'user_id',
                    'model'  => \App\Models\User::class,
                    'fields' => ['name', 'email', 'mobile_number', 'image', 'is_active'],
                ],
            ],
            'aliases' => [
                'name'           => 'user.name',
                'driver_name'    => 'user.name',
                'photo'          => 'user.image',
                'image'          => 'user.image',
                'licence_number' => 'license_no',
                'license_number' => 'license_no',
                'vehicle_number' => 'vehicle_no',
            ],
        ],
        'calendar_events' => [
            'model'  => \App\Models\Calendar\TimeTable::class,
            'label'  => 'Calendar events — meetings, holidays, activities. `date` is the day, event_type the kind.',
            'search' => ['title', 'description', 'event_type'],
        ],
        'school_info' => [
            'model'  => \App\Models\Admin\SchoolInfo::class,
            'label'  => 'The school\'s own profile: about_school, address, contacts, website details, vision/mission/values/goals.',
            'search' => ['about_school', 'school_email', 'school_mobile', 'school_address'],
        ],
        'management_team' => [
            'model'   => \App\Models\Admin\SchoolManagementTeam::class,
            'label'   => 'Management team shown on the school website.',
            'search'  => ['name', 'designation'],
            'pin_via' => ['column' => 'school_info_id', 'model' => \App\Models\Admin\SchoolInfo::class],
        ],
        'rules' => [
            'model'  => \App\Models\Admin\RulesAndRegulation::class,
            'label'  => 'School rules and regulations; the text is in `content`.',
            'search' => ['content'],
        ],
        'fee_settings' => [
            'model'  => \App\Models\Admin\Fee\FeeSettings::class,
            'label'  => 'Fee module settings — late fee rules and the like.',
            'search' => [],
        ],
        'contact_messages_students' => [
            'model'  => \App\Models\Admin\ContactAdminStudent::class,
            'label'  => 'Messages students sent the school office. topic is the subject, student_query the message, admin_reply / admin_text the office\'s answer.',
            'search' => ['topic', 'student_query', 'admin_reply'],
        ],
        'contact_messages_teachers' => [
            'model'  => \App\Models\Admin\ContactAdminTeacher::class,
            'label'  => 'Messages teachers sent the school office. topic is the subject, teacher_query the message, admin_reply / admin_text the office\'s answer.',
            'search' => ['topic', 'teacher_query', 'admin_reply'],
        ],
        'school_enquiries' => [
            'model'  => \App\Models\Admin\AdminEnquiry::class,
            'label'  => 'General enquiries received by the school. full_name is who wrote in, description what they asked.',
            'search' => ['full_name', 'email', 'mobile_number', 'description', 'type'],
        ],
        // Raised BY a school and answered by SuperLMS. The school panel shows
        // each of these too, so the school's own login must be able to read
        // them — pinned to itself, exactly like everything above.
        'credit_queries' => [
            'model'  => \App\Models\SuperAdmin\CreditQuery::class,
            'label'  => 'Credit requests this school raised with SuperLMS: amount, heading, reason, status (pending / approved / denied / processing), start and end date, per-day penalty, admin remark, and when it was approved or collected.',
            'search' => ['heading', 'reason', 'status', 'admin_remark'],
        ],
        'support_messages' => [
            'model'  => \App\Models\Admin\ContactSuperAdmin::class,
            'label'  => 'Support messages sent to SuperLMS. topic is the subject, admin_query is what was asked, super_admin_reply / super_admin_text is the answer.',
            'search' => ['topic', 'admin_query', 'super_admin_reply'],
        ],
        'ratings' => [
            'model'  => \App\Models\Admin\RateLms::class,
            'label'  => 'Ratings and feedback left about the LMS itself.',
            'search' => ['feedback', 'status'],
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
            'search' => ['payment_mode', 'receipt_number', 'remark', 'academic_year'],
        ],
        'platform_fee_structures' => [
            'model'  => \App\Models\SuperAdmin\SuperAdminFeeStructure::class,
            'label'  => 'What SuperLMS charges its schools. fee_label is the name, fee_type the kind.',
            'search' => ['fee_label', 'fee_type', 'academic_year'],
        ],
        'platform_employees' => [
            'model'  => \App\Models\SuperAdmin\SuperAdminEmployee::class,
            'label'  => 'SuperLMS\'s own staff.',
            'search' => ['name', 'email', 'mobile', 'designation'],
        ],
        'demo_requests' => [
            'model'  => \App\Models\WebsiteDemo::class,
            'label'  => 'Demo requests from the marketing site.',
            'search' => ['full_name', 'email', 'phone', 'school_name', 'city', 'role', 'remark'],
        ],
        'website_contacts' => [
            'model'  => \App\Models\WebsiteContact::class,
            'label'  => 'Contact-form messages from the marketing site.',
            'search' => ['full_name', 'email', 'phone_number', 'school_name', 'subject', 'description'],
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


    /**
     * Which module each record type belongs to.
     *
     * A login only ever sees the entities of the modules it was granted, so
     * this is a permission table as much as a grouping. Anything not named here
     * falls back to the school profile group, which everyone can read.
     *
     * @var array<string,string>
     */
    public const MODULE_OF = [
        // People
        'students'            => 'students',
        'teachers'            => 'teachers',
        'employees'           => 'teachers',
        'logins'              => 'users',
        'staff_profiles'      => 'users',

        // Structure
        'classes'             => 'classes',
        'sections'            => 'classes',
        'subjects'            => 'classes',
        'class_subjects'      => 'classes',
        'section_subjects'    => 'classes',
        'teacher_subjects'    => 'teachers',
        'teacher_sections'    => 'teachers',
        'teacher_class_assignments' => 'teachers',

        // Attendance
        'student_attendance'  => 'attendance',
        'teacher_attendance'  => 'attendance',
        'employee_attendance' => 'attendance',

        // Exams
        'exams'               => 'exams',
        'exam_marks'          => 'exams',
        'subject_marks'       => 'exams',
        'exam_datesheets'     => 'exams',
        'exam_papers'         => 'exams',
        'syllabus_chapters'   => 'exams',
        'report_cards'        => 'exams',
        'seating_plans'       => 'exams',
        'seating_rooms'       => 'exams',
        'seat_assignments'    => 'exams',
        'invigilators'        => 'exams',

        // Money
        'fee_structures'      => 'fees',
        'fee_payments'        => 'fees',
        'fee_concessions'     => 'fees',
        'fee_cycles'          => 'fees',
        'fee_settings'        => 'fees',
        'ledger'              => 'ledger',
        'salary_payments'     => 'payroll',
        'credit_queries'      => 'credit',

        // Transport
        'transport_routes'        => 'transport',
        'transport_students'      => 'transport',
        'transport_fee_payments'  => 'transport',
        'drivers'                 => 'transport',

        // Teaching
        'homework'             => 'homework',
        'homework_completions' => 'homework',
        'assignments'          => 'homework',
        'chapters'             => 'syllabus',
        'topics'               => 'syllabus',
        'student_syllabus'     => 'syllabus',
        'timetable'            => 'timetable',
        'teacher_arrangements' => 'timetable',
        'teacher_availability' => 'timetable',
        'calendar_events'      => 'calendar',

        // Paperwork
        'announcements'         => 'announcements',
        'books'                 => 'library',
        'certificates'          => 'certificates',
        'transfer_certificates' => 'certificates',
        'student_id_cards'      => 'idcards',
        'teacher_id_cards'      => 'idcards',
        'employee_id_cards'     => 'idcards',
        'admit_cards'           => 'idcards',
        'admission_enquiries'   => 'enquiries',
        'school_enquiries'      => 'enquiries',

        // The school's own profile and its thread with SuperLMS — no screen to
        // grant, so nobody is refused them.
        'school_info'                 => 'overview',
        'management_team'             => 'overview',
        'school_documents'            => 'overview',
        'rules'                       => 'overview',
        'contact_messages_students'   => 'support',
        'contact_messages_teachers'   => 'support',
        'support_messages'            => 'support',
        'ratings'                     => 'support',

        // Platform-only records.
        'schools'                  => 'platform',
        'platform_fee_payments'    => 'platform',
        'platform_fee_structures'  => 'platform',
        'platform_employees'       => 'platform',
        'demo_requests'            => 'platform',
        'website_contacts'         => 'platform',
        'payment_transactions'     => 'platform',
    ];

    /** The module an entity belongs to. */
    public static function moduleOf(string $key): string
    {
        return self::MODULE_OF[$key] ?? 'overview';
    }

    /**
     * The record types this caller may read: their panel's set, filtered down
     * to the modules their login was actually granted.
     *
     * @return array<string,array<string,mixed>>
     */
    public static function forScope(LmsScope $scope): array
    {
        $all = $scope->isPlatform()
            ? array_merge(self::SCHOOL, self::PLATFORM)
            : self::SCHOOL;

        return array_filter(
            $all,
            fn (string $key) => $scope->can(self::moduleOf($key)),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * Record types this caller would have, were their login granted the
     * screens — so a refusal can name the module instead of pretending the
     * data does not exist.
     *
     * @return array<string,string>  entity => module
     */
    public static function blockedForScope(LmsScope $scope): array
    {
        $all = $scope->isPlatform()
            ? array_merge(self::SCHOOL, self::PLATFORM)
            : self::SCHOOL;

        $blocked = [];

        foreach (array_keys($all) as $key) {
            $module = self::moduleOf($key);

            if (! $scope->can($module)) {
                $blocked[$key] = $module;
            }
        }

        return $blocked;
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
