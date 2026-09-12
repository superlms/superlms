<?php

namespace App\Services\Gemini;

use App\Models\AboutApp;
use App\Models\Admin\AdmissionEnquiry;
use App\Models\Admin\AdminEmployee;
use App\Models\Admin\Announcement;
use App\Models\Admin\Book;
use App\Models\Admin\Certificate;
use App\Models\Admin\ContactSuperAdmin;
use App\Models\Admin\AdminAttendance;
use App\Models\Admin\AdminSalaryPayment;
use App\Models\Admin\Exam;
use App\Models\Admin\ExamCopy;
use App\Models\Admin\ExamDatesheet;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\HomeWork;
use App\Models\Admin\LedgerTransaction;
use App\Models\Admin\RateLms;
use App\Models\Admin\TeacherTimeTable;
use App\Models\Admin\TermAndCondition;
use App\Models\Admin\Transportation;
use App\Models\Admin\TransferCertificate;
use App\Models\Admin\TransportFeePayment;
use App\Models\Calendar\TimeTable as CalendarEvent;
use App\Models\Organization;
use App\Models\PrivacyPolicy;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Models\SuperAdmin\CreditQuery;
use App\Models\SuperAdmin\SuperAdminFeePayment;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use App\Models\TermOfUse;
use App\Models\User;
use App\Models\WebsiteDemo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The read-only query surface Gemini is allowed to call.
 *
 * Every tool is a hand-written Eloquent query — the model never supplies SQL, a
 * table name or an organization id. Arguments are whitelisted and clamped here.
 *
 * READ-ONLY, and not merely by convention: every call runs inside a transaction
 * that is always rolled back ({@see run()}), so nothing a tool touches can
 * survive the call. Creating, updating or deleting anything is the panel's job,
 * never the assistant's.
 *
 * ── Who can read what ────────────────────────────────────────────────────
 * One method decides it for every query: {@see effectiveOrganizationId()}.
 *
 *  - A school user (admin / sub-admin / accounts) is PINNED to their own
 *    organization_id, taken from their own user row. No argument, no phrasing
 *    and no injected instruction can widen it — a `school` argument is not even
 *    declared for them, and would be ignored if the model invented one.
 *  - A sub-super-admin limited to one school is pinned exactly the same way,
 *    rather than relying on the partial set of global scopes the super-admin
 *    middleware installs.
 *  - Only a full super-admin resolves to null, which means "no organization
 *    filter" — the whole platform, which is the point of that panel.
 *
 * Rows handed to a cross-organization reader carry the school name, so an
 * answer spanning schools can never silently merge two of them.
 *
 * ── Which tools exist at all ─────────────────────────────────────────────
 * A second gate sits next to the first: every tool belongs to a MODULE, and a
 * tool whose module this login was not granted is never declared, and would be
 * refused if the model named it anyway. A sub-admin who cannot open the Fee
 * screen has no fee tool, and the refusal says so — "you were not granted
 * that screen" is a different answer from "there is no data", and giving the
 * second when the first is true is how an assistant ends up lying.
 */
class LmsToolbox
{
    /**
     * The most rows any one tool call returns. Generous on purpose: a listing
     * that is cut short costs another round-trip, and round-trips are what run
     * a question out of road.
     */
    private const MAX_ROWS = 100;

    /** How day_of_week is stored on the timetable (1 = Monday). */
    private const WEEKDAYS = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    /** Record types only the platform panel may list. */
    private const PLATFORM_ENTITIES = ['demo_requests', 'schools'];

    private const SCHOOL_ENTITIES = [
        'announcements', 'homework', 'exams', 'certificates', 'transfer_certificates',
        'admission_enquiries', 'ledger', 'transport', 'books', 'fee_structures',
        'credit_queries', 'support_messages', 'ratings',
    ];

    /** The module each `recent_records` entity belongs to. */
    private const RECENT_ENTITY_MODULE = [
        'announcements'         => 'announcements',
        'homework'              => 'homework',
        'exams'                 => 'exams',
        'certificates'          => 'certificates',
        'transfer_certificates' => 'certificates',
        'admission_enquiries'   => 'enquiries',
        'ledger'                => 'ledger',
        'transport'             => 'transport',
        'books'                 => 'library',
        'fee_structures'        => 'fees',
        'credit_queries'        => 'credit',
        'support_messages'      => 'support',
        'ratings'               => 'support',
        'demo_requests'         => 'platform',
        'schools'               => 'platform',
    ];

    /**
     * The module each tool belongs to. A login without that module never sees
     * the tool declared, and cannot run it by name either.
     */
    private const TOOL_MODULE = [
        'search_students'       => 'students',
        'student_profile'       => 'students',
        'class_roster'          => 'students',
        'search_staff'          => 'teachers',
        'search_users'          => 'users',
        'fee_payments'          => 'fees',
        'fee_defaulters'        => 'fees',
        'attendance_report'     => 'attendance',
        'staff_attendance'      => 'attendance',
        'exam_results'          => 'exams',
        'exam_schedule'         => 'exams',
        'payroll_report'        => 'payroll',
        'ledger_report'         => 'ledger',
        'class_timetable'       => 'timetable',
        'credit_requests'       => 'credit',
        // Always available: each one filters what it shows by module itself.
        'daily_summary'         => 'overview',
        'describe_data'         => 'overview',
        'query_records'         => 'overview',
        'aggregate_records'     => 'overview',
        'recent_records'        => 'overview',
        'policy_document'       => 'support',
        // Platform panel only.
        'search_schools'        => 'platform',
        'school_overview'       => 'platform',
        'platform_fee_payments' => 'platform',
    ];

    public function __construct(private readonly LmsScope $scope) {}

    /**
     * Function declarations in the shape Gemini expects.
     *
     * @return array<int,array<string,mixed>>
     */
    public function declarations(): array
    {
        // The platform panel gets the same school tools — that is how a
        // super-admin reads any school's students, staff, fees and attendance —
        // plus a `school` argument on each to narrow to one, and the
        // platform-only tools underneath.
        $tools = $this->schoolToolDeclarations($this->scope->readsWholePlatform());

        if ($this->scope->isPlatform()) {
            $tools = array_merge($tools, $this->platformToolDeclarations());
        }

        // Drop anything this login was not granted. Done here rather than only
        // at call time: a tool the model cannot see is a tool it cannot be
        // talked into trying, and the declaration list is what it reasons over.
        return array_values(array_filter(
            $tools,
            fn (array $tool) => $this->allows((string) $tool['name']),
        ));
    }

    /** Whether this login's modules cover a tool. */
    private function allows(string $tool): bool
    {
        return $this->scope->can(self::TOOL_MODULE[$tool] ?? 'overview');
    }

    /**
     * Run one tool call. Never throws — a failure is reported back to the model
     * as data so it can say "I couldn't read that" instead of the panel 500ing.
     *
     * @param  array<string,mixed>  $args
     * @return array<string,mixed>
     */
    public function run(string $name, array $args): array
    {
        if (! $this->allows($name)) {
            $module = self::TOOL_MODULE[$name] ?? 'overview';

            return ['error' => $module === 'platform'
                ? 'That is platform (super-admin) data. This panel only ever sees its own school — say so; it is not a missing permission the school can grant itself.'
                : sprintf(
                    'This login was not granted the %s screen, so that data cannot be read here. '
                    . 'Tell the user it is a permission limit on their own account, not missing data '
                    . '— a full admin can grant the screen from the Users page.',
                    LmsAccess::label($module),
                )];
        }

        if (! in_array($name, array_column($this->declarations(), 'name'), true)) {
            return ['error' => 'Unknown tool for this panel.'];
        }

        try {
            // Read-only, enforced rather than promised: the whole call runs in a
            // transaction we always roll back, so even a future tool that wrote
            // something by accident could not leave it behind. Reads are
            // unaffected.
            DB::beginTransaction();

            try {
                return $this->dispatch($name, $args);
            } finally {
                DB::rollBack();
            }
        } catch (\Throwable $e) {
            Log::warning('gemini.tool failed', ['tool' => $name, 'error' => $e->getMessage()]);

            // A table this installation never got (the schema drifts between
            // deployments). Say that, rather than letting the model report an
            // empty result as "there is no data".
            if (str_contains($e->getMessage(), "doesn't exist")) {
                return ['error' => 'That module is not set up on this installation, so there is nothing to read.'];
            }

            return ['error' => 'That data could not be read right now.'];
        }
    }

    /**
     * @param  array<string,mixed>  $args
     * @return array<string,mixed>
     */
    private function dispatch(string $name, array $args): array
    {
        return match ($name) {
            'daily_summary'         => $this->dailySummary($args),
            'policy_document'       => $this->policyDocument($args),
            'credit_requests'       => $this->creditRequests($args),
            'search_students'       => $this->searchStudents($args),
            'student_profile'       => $this->studentProfile($args),
            'class_roster'          => $this->classRoster($args),
            'search_staff'          => $this->searchStaff($args),
            'search_users'          => $this->searchUsers($args),
            'fee_payments'          => $this->feePayments($args),
            'fee_defaulters'        => $this->feeDefaulters($args),
            'attendance_report'     => $this->attendanceReport($args),
            'exam_results'          => $this->examResults($args),
            'exam_schedule'         => $this->examSchedule($args),
            'staff_attendance'      => $this->staffAttendance($args),
            'payroll_report'        => $this->payrollReport($args),
            'ledger_report'         => $this->ledgerReport($args),
            'class_timetable'       => $this->classTimetable($args),
            'describe_data'         => $this->describeData($args),
            'query_records'         => $this->queryRecords($args),
            'aggregate_records'     => $this->aggregateRecords($args),
            'recent_records'        => $this->recentRecords($args),
            'search_schools'        => $this->searchSchools($args),
            'school_overview'       => $this->schoolOverview($args),
            'platform_fee_payments' => $this->platformFeePayments($args),
            default                 => ['error' => 'Tool not implemented.'],
        };
    }

    // ══════════════════════════════════════════════════════════════════
    // The access gate — every query funnels through these
    // ══════════════════════════════════════════════════════════════════

    /**
     * Which organization this call may read, or null for "all of them".
     *
     * A pinned caller is pinned first and the `school` argument is never even
     * consulted, so the model cannot talk its way into another school.
     */
    private function effectiveOrganizationId(array $args): ?int
    {
        if ($forced = $this->scope->forcedOrganizationId()) {
            return $forced;
        }

        return $this->findSchool($args['school'] ?? null)?->id;
    }

    /** True when this call spans schools, so rows must name theirs. */
    private function spansSchools(?int $orgId): bool
    {
        return $orgId === null;
    }

    private function pin(Builder $query, ?int $orgId, string $column = 'organization_id'): Builder
    {
        return $orgId ? $query->where($column, $orgId) : $query;
    }

    /** Told back to the model so it never mislabels whose numbers these are. */
    private function coverage(?int $orgId): string
    {
        if (! $orgId) {
            return 'all schools on the platform';
        }

        return Organization::find($orgId)?->name ?: ('school #' . $orgId);
    }

    // ══════════════════════════════════════════════════════════════════
    // Declarations
    // ══════════════════════════════════════════════════════════════════

    /** @return array<int,array<string,mixed>> */
    private function schoolToolDeclarations(bool $withSchoolArg): array
    {
        $school = $withSchoolArg
            ? ['school' => $this->str('Limit to one school, by name or serial number. Leave it out to read across every school on the platform.')]
            : [];

        $note = $withSchoolArg
            ? ' Covers every school unless a school is named.'
            : ' Covers this school only.';

        $entities = array_values(array_filter(
            $this->scope->isPlatform()
                ? array_merge(self::SCHOOL_ENTITIES, self::PLATFORM_ENTITIES)
                : self::SCHOOL_ENTITIES,
            fn (string $entity) => $this->scope->can(self::RECENT_ENTITY_MODULE[$entity] ?? 'overview'),
        ));

        return [
            [
                'name'        => 'daily_summary',
                'description' => 'One day of the school in a single call: student attendance AND staff attendance with whether each was actually marked, fee collected, ledger money in/out, salaries paid, new admissions, enquiries received, homework and announcements posted, exams running, calendar events and birthdays. Use it for "aaj ki summary", "today\'s report", "kya hua aaj", "kal ka summary" — and for "is attendance marked today", which it answers for students and staff at once.' . $note,
                'parameters'  => $this->schema($school + [
                    'date' => $this->str('The day, YYYY-MM-DD. Defaults to today.'),
                ]),
            ],
            [
                'name'        => 'credit_requests',
                'description' => 'Credit requests raised with SuperLMS: heading, reason, amount, status (pending / processing / approved / denied), the period it covers, the per-day penalty, what is owed as of today and whether it has been collected. Use it for anything about credit, a credit enquiry, a credit request or its status.' . $note,
                'parameters'  => $this->schema($school + [
                    'status' => $this->enum(['pending', 'processing', 'approved', 'denied', 'any'], 'Filter by status (default any).'),
                    'limit'  => $this->int('Max rows (default 20, max 100).'),
                ]),
            ],
            [
                'name'        => 'policy_document',
                'description' => 'The text of the SuperLMS documents every panel links to under More: the Privacy Policy, the Terms of Use, the Terms & Conditions, and About App (company name, CIN, contact details, team). Call it whenever the user asks what the privacy policy or the terms say, or who runs SuperLMS. With no search word it returns the section headings and an excerpt of each; pass `search` or `section` to get the full text of the ones that matter.',
                'parameters'  => $this->schema([
                    'document' => $this->enum(['privacy_policy', 'terms_of_use', 'terms_and_conditions', 'about_app', 'list'], 'Which document. "list" names the documents that exist.'),
                    'search'   => $this->str('Only return sections whose heading or text contains this word — returned in full.'),
                    'section'  => $this->str('One section, by its heading or its number. Returned in full.'),
                ], ['document']),
            ],
            [
                'name'        => 'search_students',
                'description' => 'Find students by name, admission number, roll number, father/mother name, class or section, and count how many match.' . $note,
                'parameters'  => $this->schema($school + [
                    'query'    => $this->str('Free text: part of a name, admission no or roll no.'),
                    'standard' => $this->str('Class name, e.g. "10th".'),
                    'section'  => $this->str('Section name, e.g. "A".'),
                    'limit'    => $this->int('Max rows to return (default 20, max 100).'),
                ]),
            ],
            [
                'name'        => 'student_profile',
                'description' => 'Full profile of one student: personal details, class, transport, total fees paid and attendance summary. Identify them by admission number or name.' . $note,
                'parameters'  => $this->schema($school + [
                    'admission_no' => $this->str('Exact admission number.'),
                    'name'         => $this->str('Full or partial student name.'),
                ]),
            ],
            [
                'name'        => 'class_roster',
                'description' => 'List every student in one class (optionally one section) with roll number and admission number. Needs one school.',
                'parameters'  => $this->schema($school + [
                    'standard' => $this->str('Class name, e.g. "10th".'),
                    'section'  => $this->str('Section name, e.g. "A".'),
                ], ['standard']),
            ],
            [
                'name'        => 'search_staff',
                'description' => 'Find teachers or non-teaching employees by name, email, phone, employee id or designation.' . $note,
                'parameters'  => $this->schema($school + [
                    'query' => $this->str('Free text to match.'),
                    'type'  => $this->enum(['teacher', 'employee', 'any'], 'Which staff list to search (default any).'),
                    'limit' => $this->int('Max rows (default 20, max 100).'),
                ]),
            ],
            [
                'name'        => 'fee_payments',
                'description' => 'Fee money actually collected from students. Returns the total plus a breakdown by payment mode and fee type, and the matching payments. Use for "how much fee collected in September", "cash vs online".' . $note,
                'parameters'  => $this->schema($school + [
                    'from'     => $this->str('Start date, YYYY-MM-DD.'),
                    'to'       => $this->str('End date, YYYY-MM-DD.'),
                    'standard' => $this->str('Limit to one class.'),
                    'fee_type' => $this->str('Limit to one fee type, e.g. tuition.'),
                    'student'  => $this->str('Student name or admission no.'),
                    'limit'    => $this->int('Max payment rows listed (default 20, max 100).'),
                ]),
            ],
            [
                'name'        => 'fee_defaulters',
                'description' => 'Students whose paid amount is below what their class is charged by the active fee structures. Use for "who has pending fees", "defaulters of 10th". Needs one school.',
                'parameters'  => $this->schema($school + [
                    'standard' => $this->str('Limit to one class.'),
                    'limit'    => $this->int('Max rows (default 20, max 100).'),
                ]),
            ],
            [
                'name'        => 'attendance_report',
                'description' => 'STUDENT attendance counts (present / absent / half day / holiday) for a date or a date range, optionally for one class, with which classes were marked and which were not. For teachers or other staff use staff_attendance instead — this tool never looks at them.' . $note,
                'parameters'  => $this->schema($school + [
                    'date'     => $this->str('A single day, YYYY-MM-DD. Defaults to today when no range is given.'),
                    'from'     => $this->str('Range start, YYYY-MM-DD.'),
                    'to'       => $this->str('Range end, YYYY-MM-DD.'),
                    'standard' => $this->str('Limit to one class.'),
                ]),
            ],
            [
                'name'        => 'exam_results',
                'description' => 'Exam marks that have actually been entered, ranked best first. Use it for anything about marks, results, toppers or performance: "who scored the highest", "top 3 of nursery", "class average in Unit Test 1", "how did this student do". Each student comes back with marks obtained, maximum, percentage, grade and whether they were absent; with no subject named every subject is added up.' . $note,
                'parameters'  => $this->schema($school + [
                    'exam'     => $this->str('Exam name, e.g. "Unit Test 1". Leave out for the latest exam that has marks.'),
                    'standard' => $this->str('Class name, e.g. "NURSERY", "10th".'),
                    'section'  => $this->str('Section name, e.g. "A".'),
                    'subject'  => $this->str('One subject, e.g. "COMPUTER". Leave out to total every subject.'),
                    'student'  => $this->str('One student, by name or admission number. Returns their subject-wise marks.'),
                    'limit'    => $this->int('How many students to list, best first (default 20, max 100) — pass 3 for "top 3".'),
                ]),
            ],
            [
                'name'        => 'exam_schedule',
                'description' => 'The datesheet: which subject is examined on which date and at what time, for a class. Use for "when is the maths paper", "exam schedule of 10th".' . $note,
                'parameters'  => $this->schema($school + [
                    'exam'     => $this->str('Exam name. Leave out for the most recent datesheet.'),
                    'standard' => $this->str('Class name.'),
                    'section'  => $this->str('Section name.'),
                    'limit'    => $this->int('Max papers to list (default 20, max 100).'),
                ]),
            ],
            [
                'name'        => 'staff_attendance',
                'description' => 'TEACHER and non-teaching EMPLOYEE attendance (present / absent / half day / holiday) for a date or a range, with who was absent and how many of the staff were marked at all. This is the only tool that reads staff attendance — attendance_report reads STUDENTS and says nothing about teachers, so never answer a question about teacher attendance from it.' . $note,
                'parameters'  => $this->schema($school + [
                    'date'  => $this->str('A single day, YYYY-MM-DD. Defaults to today when no range is given.'),
                    'from'  => $this->str('Range start, YYYY-MM-DD.'),
                    'to'    => $this->str('Range end, YYYY-MM-DD.'),
                    'type'  => $this->enum(['teacher', 'employee', 'any'], 'Which staff list (default any).'),
                    'limit' => $this->int('Max names listed (default 20, max 100).'),
                ]),
            ],
            [
                'name'        => 'payroll_report',
                'description' => 'Staff salary payments: totals paid and pending, by month, with the matching payment rows. Use for "salary paid this month", "whose salary is pending".' . $note,
                'parameters'  => $this->schema($school + [
                    'month'    => $this->str('One month, YYYY-MM.'),
                    'from'     => $this->str('Range start, YYYY-MM-DD (on payment date).'),
                    'to'       => $this->str('Range end, YYYY-MM-DD.'),
                    'status'   => $this->enum(['paid', 'pending', 'any'], 'Filter by payment status (default any).'),
                    'employee' => $this->str('One employee, by name.'),
                    'limit'    => $this->int('Max rows (default 20, max 100).'),
                ]),
            ],
            [
                'name'        => 'ledger_report',
                'description' => 'The school ledger: money in (credit) versus money out (expense) for a period, the net, and the matching entries. Use for "kharcha kitna hua", "expenses this month".' . $note,
                'parameters'  => $this->schema($school + [
                    'from'  => $this->str('Start date, YYYY-MM-DD.'),
                    'to'    => $this->str('End date, YYYY-MM-DD.'),
                    'type'  => $this->enum(['credit', 'expense', 'any'], 'Only money in, only money out, or both (default any).'),
                    'party' => $this->str('Match the party / reason text.'),
                    'limit' => $this->int('Max entries listed (default 20, max 100).'),
                ]),
            ],
            [
                'name'        => 'class_timetable',
                'description' => 'Periods from the timetable — which subject, which teacher, which day and time — for one class and section, or for one teacher. Needs one school.',
                'parameters'  => $this->schema($school + [
                    'standard' => $this->str('Class name.'),
                    'section'  => $this->str('Section name.'),
                    'teacher'  => $this->str('Teacher name, to read that teacher\'s week instead of a class.'),
                    'day'      => $this->str('One day, e.g. "Monday". Leave out for the whole week.'),
                    'limit'    => $this->int('Max periods (default 40, max 100).'),
                ]),
            ],
            [
                'name'        => 'search_users',
                'description' => 'Find login accounts — admins, sub-admins, accounts users, teachers and students — by name, email, mobile or role, with their profile photo and when they last signed in. Use for "who are the admins here", "how many teacher logins exist", "find this email", "whose account is disabled". Returns a count per role as well as the matching accounts.' . $note,
                'parameters'  => $this->schema($school + [
                    'query'  => $this->str('Free text: name, email or mobile.'),
                    'role'   => $this->enum(['admin', 'sub-admin', 'accounts', 'teacher', 'user', 'super-admin', 'sub-super-admin', 'any'], 'Limit to one role (default any). "user" is a student login.'),
                    'active' => $this->enum(['yes', 'no', 'any'], 'Only enabled logins, only disabled ones, or both (default any).'),
                    'limit'  => $this->int('Max rows (default 20, max 100).'),
                ]),
            ],
            [
                'name'        => 'describe_data',
                'description' => 'What records this panel can read, and what fields each one has. Call it with no arguments to list the record types, or with one entity to see its exact field names before querying it. Use this whenever a question asks about something the other tools do not obviously cover — a photo, an address, a route, a status, any column at all.' . $note,
                'parameters'  => $this->schema($school + [
                    'entity' => $this->str('Record type to describe, e.g. "students". Leave out to list everything readable.'),
                ]),
            ],
            [
                'name'        => 'query_records',
                'description' => 'Read any record type from describe_data, with filters, and get the rows back. This is the general way to answer anything the purpose-built tools do not: for example students whose photo is uploaded (entity "students", where field "image" op "not_empty"), students from one city, books by an author, unpaid salaries. Ids that name something — class, section, subject, student, exam, school — come back resolved to names as well.' . $note,
                'parameters'  => $this->schema($school + [
                    'entity'     => $this->str('Record type from describe_data, e.g. "students".'),
                    'search'     => $this->str('Free text matched against that record type\'s searchable fields.'),
                    'standard'   => $this->str('Narrow to one class by NAME, e.g. "NURSERY" — no need to know its id.'),
                    'section'    => $this->str('Narrow to one section by NAME, e.g. "A".'),
                    'where'      => $this->arr('Filters, all of which must hold.', $this->schema([
                        'field' => $this->str('Field name, exactly as describe_data spells it.'),
                        'op'    => $this->enum(['eq', 'ne', 'gt', 'gte', 'lt', 'lte', 'like', 'in', 'is_null', 'not_null', 'empty', 'not_empty'], 'Comparison. Use not_empty for "has a value" (a photo, a phone number) and empty for "is missing".'),
                        'value' => $this->str('The value to compare against. Leave out for is_null / not_null / empty / not_empty; for "in", separate values with commas. An id field may be given a NAME instead — field "standard_id" with value "NURSERY" works.'),
                    ], ['field', 'op'])),
                    'fields'     => $this->arr('Which fields to return. Leave out for a sensible default, or pass "*" for every field there is.', $this->str('Field name, or "*" for all of them.')),
                    'all_fields' => $this->bool('True to return EVERY field of the record, not the default dozen. Use it whenever the user asks for full details, complete data, or "saara data".'),
                    'order_by'   => $this->str('Field to sort by (default the newest first).'),
                    'direction'  => $this->enum(['asc', 'desc'], 'Sort direction (default desc).'),
                    'count_only' => $this->bool('True to return only how many rows match, without listing them.'),
                    'limit'      => $this->int('Max rows (default 20, max 100).'),
                ], ['entity']),
            ],
            [
                'name'        => 'aggregate_records',
                'description' => 'Count, total or average any field of any record type, optionally grouped. Use for "how many students per class", "total expense by reason", "average marks by section".' . $note,
                'parameters'  => $this->schema($school + [
                    'entity'   => $this->str('Record type from describe_data.'),
                    'standard' => $this->str('Narrow to one class by NAME.'),
                    'section'  => $this->str('Narrow to one section by NAME.'),
                    'metric'   => $this->enum(['count', 'sum', 'avg', 'min', 'max'], 'What to work out (default count).'),
                    'field'    => $this->str('Field to total or average. Required for anything but count.'),
                    'group_by' => $this->str('Field to group by, e.g. "standard_id", "payment_mode".'),
                    'search'   => $this->str('Free text filter, as in query_records.'),
                    'where'    => $this->arr('Filters, as in query_records.', $this->schema([
                        'field' => $this->str('Field name.'),
                        'op'    => $this->enum(['eq', 'ne', 'gt', 'gte', 'lt', 'lte', 'like', 'in', 'is_null', 'not_null', 'empty', 'not_empty'], 'Comparison.'),
                        'value' => $this->str('Value to compare against.'),
                    ], ['field', 'op'])),
                ], ['entity']),
            ],
            [
                'name'        => 'recent_records',
                'description' => 'The most recent rows of one record type.' . $note,
                'parameters'  => $this->schema($school + [
                    'entity' => $this->enum($entities, 'Which record type to list.'),
                    'limit'  => $this->int('Max rows (default 10, max 100).'),
                ], ['entity']),
            ],
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function platformToolDeclarations(): array
    {
        return [
            [
                'name'        => 'search_schools',
                'description' => 'Find schools (organizations) on the platform by name, serial number, email, board, city or state.',
                'parameters'  => $this->schema([
                    'query'  => $this->str('Free text to match.'),
                    'status' => $this->enum(['active', 'inactive', 'any'], 'Filter by account status (default any).'),
                    'limit'  => $this->int('Max rows (default 20, max 100).'),
                ]),
            ],
            [
                'name'        => 'school_overview',
                'description' => 'Everything about one school: contact details, student/teacher/employee/login counts, classes, fees collected from its students and platform fees it has paid.',
                'parameters'  => $this->schema([
                    'school' => $this->str('School name or serial number.'),
                ], ['school']),
            ],
            [
                'name'        => 'platform_fee_payments',
                'description' => 'Platform fees paid by schools to SuperLMS. Returns the total and the matching payments.',
                'parameters'  => $this->schema([
                    'from'   => $this->str('Start date, YYYY-MM-DD.'),
                    'to'     => $this->str('End date, YYYY-MM-DD.'),
                    'school' => $this->str('Limit to one school name or serial.'),
                    'limit'  => $this->int('Max rows (default 20, max 100).'),
                ]),
            ],
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    // People
    // ══════════════════════════════════════════════════════════════════

    private function searchStudents(array $a): array
    {
        $orgId = $this->effectiveOrganizationId($a);
        $cross = $this->spansSchools($orgId);

        $q = $this->pin(StudentDetail::query(), $orgId)
            ->with(array_values(array_filter(['standard:id,name', 'section:id,name', $cross ? 'organization:id,name' : null])));

        if ($text = $this->text($a['query'] ?? null)) {
            $q->where(fn ($w) => $w
                ->where('full_name', 'like', "%{$text}%")
                ->orWhere('admission_no', 'like', "%{$text}%")
                ->orWhere('roll_no', 'like', "%{$text}%")
                ->orWhere('father_name', 'like', "%{$text}%")
                ->orWhere('mother_name', 'like', "%{$text}%"));
        }

        $std = $this->standardId($a['standard'] ?? null, $orgId);
        if ($std) {
            $q->where('standard_id', $std);
        }
        if ($sec = $this->sectionId($a['section'] ?? null, $std, $orgId)) {
            $q->where('section_id', $sec);
        }

        $rows = (clone $q)->orderBy('full_name')->limit($this->limit($a, 20))->get()
            ->map(fn ($s) => $this->clean([
                'school'       => $cross ? ($s->organization->name ?? null) : null,
                'name'         => $s->full_name,
                'admission_no' => $s->admission_no,
                'roll_no'      => $s->roll_no,
                'class'        => $s->standard->name ?? null,
                'section'      => $s->section->name ?? null,
                'father'       => $s->father_name,
                'phone'        => $s->phone,
            ]))->all();

        // Two children with the same name are common; when the search text is
        // a name and more than one came back, say so, so a follow-up about
        // "their" fees is asked about rather than guessed at.
        $names = array_count_values(array_filter(array_column($rows, 'name')));
        $dupes = array_keys(array_filter($names, fn (int $n) => $n > 1));

        return $this->clean([
            'covers'   => $this->coverage($orgId),
            'matched'  => (clone $q)->count(),
            'showing'  => count($rows),
            'students' => $rows,
            'same_name' => $dupes !== []
                ? 'More than one student is called: ' . implode(', ', $dupes)
                . '. Before answering anything about one of them, ask which one — by admission number,'
                . ' in the language the user asked in.'
                : null,
        ]);
    }

    private function studentProfile(array $a): array
    {
        $orgId = $this->effectiveOrganizationId($a);
        $cross = $this->spansSchools($orgId);

        $q = $this->pin(StudentDetail::query(), $orgId)
            ->with(array_values(array_filter(['standard:id,name', 'section:id,name', $cross ? 'organization:id,name' : null])));

        if ($adm = $this->text($a['admission_no'] ?? null)) {
            $q->where('admission_no', $adm);
        } elseif ($name = $this->text($a['name'] ?? null)) {
            $q->where('full_name', 'like', "%{$name}%");
        } else {
            return ['error' => 'Give an admission number or a name.'];
        }

        $matches = $q->with('section:id,name')->limit(8)->get();

        if ($matches->isEmpty()) {
            return ['found' => false, 'message' => 'No student matched in ' . $this->coverage($orgId) . '.'];
        }

        // Several students share a name in almost every school. Handing back the
        // first one would put another child's fees and attendance under the name
        // the user asked about, so this comes back as a question instead — with
        // exactly the columns that tell them apart.
        if ($matches->count() > 1) {
            return [
                'found'       => false,
                'ambiguous'   => true,
                'match_count' => $matches->count(),
                'message'     => 'STOP: ' . $matches->count() . ' students match that name. Do NOT answer about any '
                    . 'one of them. List these candidates with their admission number, class and '
                    . 'father\'s name, and ask the user which one they mean — in the language they '
                    . 'asked in.',
                'candidates'  => $matches->map(fn ($s) => $this->clean([
                    'school'       => $cross ? ($s->organization->name ?? null) : null,
                    'name'         => $s->full_name,
                    'admission_no' => $s->admission_no,
                    'roll_no'      => $s->roll_no,
                    'class'        => $s->standard->name ?? null,
                    'section'      => $s->section->name ?? null,
                    'father'       => $s->father_name,
                    'phone'        => $s->phone,
                ]))->all(),
                'next_step'   => 'Once the user picks one, call student_profile again with that admission_no.',
            ];
        }

        $s = $matches->first();

        // Pin the follow-up reads to the student's OWN school, not to the
        // request's scope: a cross-school search may have landed anywhere.
        $stuOrg = (int) $s->organization_id;

        $paid = (float) FeePayment::where('organization_id', $stuOrg)
            ->where('student_detail_id', $s->id)->sum('amount');

        $payments = FeePayment::where('organization_id', $stuOrg)
            ->where('student_detail_id', $s->id)->orderByDesc('payment_date')->limit(10)
            ->get(['receipt_number', 'fee_type', 'amount', 'payment_mode', 'payment_date'])
            ->map(fn ($p) => [
                'receipt' => $p->receipt_number,
                'type'    => $p->fee_type,
                'amount'  => (float) $p->amount,
                'mode'    => $p->payment_mode,
                'date'    => optional($p->payment_date)->toDateString(),
            ])->all();

        $att = StudentAttendance::where('organization_id', $stuOrg)
            ->where('student_detail_id', $s->id)
            ->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');

        return [
            'found'   => true,
            'student' => $this->clean([
                'school'            => $cross ? ($s->organization->name ?? null) : null,
                'name'              => $s->full_name,
                'admission_no'      => $s->admission_no,
                'roll_no'           => $s->roll_no,
                'class'             => $s->standard->name ?? null,
                'section'           => $s->section->name ?? null,
                'father'            => $s->father_name,
                'mother'            => $s->mother_name,
                'gender'            => $s->gender,
                'dob'               => optional($s->dob)->toDateString(),
                'date_of_admission' => optional($s->date_of_admission)->toDateString(),
                'phone'             => $s->phone,
                'email'             => $s->email,
                'address'           => $s->local_address,
                'city'              => $s->city,
                'transport'         => (bool) $s->transportation_required,
            ]),
            'fees'       => ['total_paid' => $paid, 'recent_payments' => $payments],
            'attendance' => [
                'present'  => (int) ($att[1] ?? 0),
                'absent'   => (int) ($att[0] ?? 0),
                'half_day' => (int) ($att[2] ?? 0),
                'holiday'  => (int) ($att[3] ?? 0),
            ],
        ];
    }

    private function classRoster(array $a): array
    {
        $orgId = $this->effectiveOrganizationId($a);

        if ($this->spansSchools($orgId)) {
            return ['error' => 'A class roster needs one school — name the school and ask again.'];
        }

        $std = $this->standardId($a['standard'] ?? null, $orgId);
        if (! $std) {
            return ['error' => 'That class does not exist in ' . $this->coverage($orgId) . '.'];
        }

        $q = $this->pin(StudentDetail::query(), $orgId)
            ->where('standard_id', $std)
            ->with('section:id,name');

        if ($sec = $this->sectionId($a['section'] ?? null, $std, $orgId)) {
            $q->where('section_id', $sec);
        }

        $rows = (clone $q)->orderBy('roll_no')->orderBy('full_name')->limit(self::MAX_ROWS)->get();

        return [
            'covers'   => $this->coverage($orgId),
            // The roster is deliberately four columns wide. Say where the rest
            // lives, so a follow-up for Aadhaar or a father's name is answered
            // instead of being reported as missing.
            'more_fields' => 'Only the basics are here. Every other student field - father_name, mother_name, aadhar_no, dob, address, phone, image (photo), transportation_required - comes from query_records with entity "students" and this class name.',
            'class'    => Standard::find($std)?->name,
            'total'    => (clone $q)->count(),
            'showing'  => $rows->count(),
            'students' => $rows->map(fn ($s) => [
                'roll_no'      => $s->roll_no,
                'name'         => $s->full_name,
                'admission_no' => $s->admission_no,
                'section'      => $s->section->name ?? null,
            ])->all(),
        ];
    }

    private function searchStaff(array $a): array
    {
        $orgId = $this->effectiveOrganizationId($a);
        $cross = $this->spansSchools($orgId);
        $type  = in_array($a['type'] ?? 'any', ['teacher', 'employee', 'any'], true) ? ($a['type'] ?? 'any') : 'any';
        $text  = $this->text($a['query'] ?? null);
        $limit = $this->limit($a, 20);

        $out = ['covers' => $this->coverage($orgId)];

        if ($type !== 'employee') {
            $q = $this->pin(TeacherDetail::query(), $orgId)
                ->with(array_values(array_filter(['user:id,name,email,mobile_number', $cross ? 'organization:id,name' : null])));

            if ($text) {
                $q->where(fn ($w) => $w
                    ->where('employee_id', 'like', "%{$text}%")
                    ->orWhere('phone', 'like', "%{$text}%")
                    ->orWhere('qualification', 'like', "%{$text}%")
                    ->orWhereHas('user', fn ($u) => $u
                        ->where('name', 'like', "%{$text}%")
                        ->orWhere('email', 'like', "%{$text}%")));
            }

            $out['teacher_count'] = (clone $q)->count();
            $out['teachers']      = $q->limit($limit)->get()->map(fn ($t) => $this->clean([
                'school'        => $cross ? ($t->organization->name ?? null) : null,
                'name'          => $t->user->name ?? null,
                'email'         => $t->user->email ?? null,
                'phone'         => $t->phone ?: ($t->user->mobile_number ?? null),
                'employee_id'   => $t->employee_id,
                'qualification' => $t->qualification,
                'joined'        => optional($t->date_of_joining)->toDateString(),
            ]))->all();
        }

        if ($type !== 'teacher') {
            $q = $this->pin(AdminEmployee::query(), $orgId);
            if ($cross) {
                $q->with('organization:id,name');
            }
            if ($text) {
                $q->where(fn ($w) => $w
                    ->where('name', 'like', "%{$text}%")
                    ->orWhere('email', 'like', "%{$text}%")
                    ->orWhere('mobile', 'like', "%{$text}%")
                    ->orWhere('designation', 'like', "%{$text}%"));
            }

            $out['employee_count'] = (clone $q)->count();
            $out['employees']      = $q->limit($limit)->get()->map(fn ($e) => $this->clean([
                'school'      => $cross ? ($e->organization->name ?? null) : null,
                'name'        => $e->name,
                'designation' => $e->designation,
                'type'        => $e->type,
                'email'       => $e->email,
                'phone'       => $e->mobile,
                'salary'      => $e->salary !== null ? (float) $e->salary : null,
                'active'      => (bool) $e->is_active,
            ]))->all();
        }

        return $out;
    }

    /**
     * Login accounts. Declared for the platform panel only — a school panel
     * never receives this tool, and the query would be pinned regardless.
     *
     * The select list below is exhaustive on purpose: password, password_plain,
     * otp and remember_token must never leave the database through here.
     */
    private function searchUsers(array $a): array
    {
        $orgId = $this->effectiveOrganizationId($a);
        $cross = $this->spansSchools($orgId);

        $q = $this->pin(User::query(), $orgId)
            ->select(['id', 'name', 'email', 'mobile_number', 'role', 'image', 'is_active', 'organization_id', 'last_login_at', 'created_at']);

        if ($cross) {
            $q->with('organization:id,name');
        }

        if ($text = $this->text($a['query'] ?? null)) {
            $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$text}%")
                ->orWhere('email', 'like', "%{$text}%")
                ->orWhere('mobile_number', 'like', "%{$text}%"));
        }

        $role = $a['role'] ?? 'any';
        if (is_string($role) && $role !== '' && $role !== 'any') {
            $q->where('role', $role);
        }

        $active = $a['active'] ?? 'any';
        if ($active === 'yes') {
            $q->where('is_active', 1);
        } elseif ($active === 'no') {
            $q->where('is_active', 0);
        }

        $byRole = (clone $q)->reorder()->select([])->selectRaw('role, COUNT(*) c')
            ->groupBy('role')->pluck('c', 'role')->all();

        return [
            'covers'  => $this->coverage($orgId),
            'matched' => (clone $q)->count(),
            'by_role' => $byRole,
            'users'   => $q->orderBy('name')->limit($this->limit($a, 20))->get()
                ->map(fn ($u) => $this->clean([
                    'school'     => $cross ? ($u->organization->name ?? null) : null,
                    'name'       => $u->name,
                    'email'      => $u->email,
                    'mobile'     => $u->mobile_number,
                    'role'       => $u->role,
                    'photo'      => $u->image ?: null,
                    'active'     => (bool) $u->is_active,
                    'last_login' => optional($u->last_login_at)->toDateTimeString(),
                ]))->all(),
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    // Money
    // ══════════════════════════════════════════════════════════════════

    private function feePayments(array $a): array
    {
        $orgId = $this->effectiveOrganizationId($a);
        $cross = $this->spansSchools($orgId);

        $q = $this->pin(FeePayment::query(), $orgId)
            ->with(array_values(array_filter(['studentDetail:id,full_name,admission_no', 'standard:id,name', $cross ? 'organization:id,name' : null])));

        [$from, $to] = $this->range($a);
        if ($from) {
            $q->whereDate('payment_date', '>=', $from);
        }
        if ($to) {
            $q->whereDate('payment_date', '<=', $to);
        }
        if ($std = $this->standardId($a['standard'] ?? null, $orgId)) {
            $q->where('standard_id', $std);
        }
        if ($ft = $this->text($a['fee_type'] ?? null)) {
            $q->where('fee_type', 'like', "%{$ft}%");
        }
        if ($stu = $this->text($a['student'] ?? null)) {
            $q->whereHas('studentDetail', fn ($s) => $s
                ->where('full_name', 'like', "%{$stu}%")
                ->orWhere('admission_no', 'like', "%{$stu}%"));
        }

        $out = [
            'covers'        => $this->coverage($orgId),
            'period'        => ['from' => $from, 'to' => $to],
            'total_amount'  => (float) (clone $q)->sum('amount'),
            'total_penalty' => (float) (clone $q)->sum('penalty_amount'),
            'total_waiver'  => (float) (clone $q)->sum('waiver_amount'),
            'payment_count' => (clone $q)->count(),
            'by_mode'       => (clone $q)->reorder()->selectRaw('payment_mode, COUNT(*) c, SUM(amount) total')
                ->groupBy('payment_mode')->get()
                ->mapWithKeys(fn ($r) => [($r->payment_mode ?: 'unspecified') => ['count' => (int) $r->c, 'total' => (float) $r->total]])->all(),
            'by_fee_type'   => (clone $q)->reorder()->selectRaw('fee_type, COUNT(*) c, SUM(amount) total')
                ->groupBy('fee_type')->get()
                ->mapWithKeys(fn ($r) => [($r->fee_type ?: 'unspecified') => ['count' => (int) $r->c, 'total' => (float) $r->total]])->all(),
        ];

        if ($cross) {
            $out['by_school'] = (clone $q)->reorder()
                ->selectRaw('organization_id, COUNT(*) c, SUM(amount) total')
                ->groupBy('organization_id')->get()
                ->mapWithKeys(fn ($r) => [
                    $this->coverage((int) $r->organization_id) => ['count' => (int) $r->c, 'total' => (float) $r->total],
                ])->all();
        }

        $out['payments'] = (clone $q)->orderByDesc('payment_date')->limit($this->limit($a, 20))->get()
            ->map(fn ($p) => $this->clean([
                'school'  => $cross ? ($p->organization->name ?? null) : null,
                'receipt' => $p->receipt_number,
                'student' => $p->studentDetail->full_name ?? null,
                'class'   => $p->standard->name ?? null,
                'type'    => $p->fee_type,
                'amount'  => (float) $p->amount,
                'penalty' => (float) $p->penalty_amount,
                'waiver'  => (float) $p->waiver_amount,
                'mode'    => $p->payment_mode,
                'date'    => optional($p->payment_date)->toDateString(),
            ]))->all();

        return $out;
    }

    private function feeDefaulters(array $a): array
    {
        $orgId = $this->effectiveOrganizationId($a);

        if ($this->spansSchools($orgId)) {
            return ['error' => 'Pending fees are worked out per school — name the school and ask again.'];
        }

        $stdId = $this->standardId($a['standard'] ?? null, $orgId);

        // What each class is charged, from the active fee structures.
        $expected = $this->pin(FeeStructure::query(), $orgId)
            ->where('is_active', true)
            ->selectRaw('standard_id, SUM(amount) total')
            ->groupBy('standard_id')
            ->pluck('total', 'standard_id');

        if ($expected->isEmpty()) {
            return ['message' => 'No active fee structures are set up for ' . $this->coverage($orgId) . ', so pending amounts cannot be worked out.'];
        }

        $q = $this->pin(StudentDetail::query(), $orgId)->with(['standard:id,name', 'section:id,name']);
        if ($stdId) {
            $q->where('standard_id', $stdId);
        }

        $paidByStudent = $this->pin(FeePayment::query(), $orgId)
            ->selectRaw('student_detail_id, SUM(amount) total')
            ->groupBy('student_detail_id')
            ->pluck('total', 'student_detail_id');

        $rows = [];
        $pendingTotal = 0.0;

        foreach ($q->orderBy('full_name')->get() as $s) {
            $due = (float) ($expected[$s->standard_id] ?? 0);
            if ($due <= 0) {
                continue;
            }
            $paid = (float) ($paidByStudent[$s->id] ?? 0);
            $gap  = round($due - $paid, 2);
            if ($gap <= 0) {
                continue;
            }

            $pendingTotal += $gap;
            $rows[] = [
                'name'         => $s->full_name,
                'admission_no' => $s->admission_no,
                'class'        => $s->standard->name ?? null,
                'section'      => $s->section->name ?? null,
                'expected'     => $due,
                'paid'         => $paid,
                'pending'      => $gap,
            ];
        }

        usort($rows, fn ($x, $y) => $y['pending'] <=> $x['pending']);
        $limit = $this->limit($a, 20);

        return [
            'covers'          => $this->coverage($orgId),
            'basis'           => 'Sum of active fee structures for the student\'s class, minus everything that student has paid.',
            'defaulter_count' => count($rows),
            'total_pending'   => round($pendingTotal, 2),
            'showing'         => min($limit, count($rows)),
            'students'        => array_slice($rows, 0, $limit),
        ];
    }

    private function platformFeePayments(array $a): array
    {
        $orgId = $this->effectiveOrganizationId($a);

        $q = $this->pin(SuperAdminFeePayment::query(), $orgId)->with('organization:id,name,serial_number');

        [$from, $to] = $this->range($a);
        if ($from) {
            $q->whereDate('payment_date', '>=', $from);
        }
        if ($to) {
            $q->whereDate('payment_date', '<=', $to);
        }

        return [
            'covers'        => $this->coverage($orgId),
            'period'        => ['from' => $from, 'to' => $to],
            'total_amount'  => (float) (clone $q)->sum('amount'),
            'payment_count' => (clone $q)->count(),
            'payments'      => (clone $q)->orderByDesc('payment_date')->limit($this->limit($a, 20))->get()
                ->map(fn ($p) => [
                    'school'  => $p->organization->name ?? null,
                    'receipt' => $p->receipt_number,
                    'amount'  => (float) $p->amount,
                    'mode'    => $p->payment_mode,
                    'date'    => optional($p->payment_date)->toDateString(),
                    'year'    => $p->academic_year,
                    'paid'    => (bool) $p->is_paid,
                ])->all(),
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    // Attendance
    // ══════════════════════════════════════════════════════════════════

    private function attendanceReport(array $a): array
    {
        $orgId = $this->effectiveOrganizationId($a);

        [$from, $to] = $this->range($a);

        if (! $from && ! $to) {
            $single = $this->date($a['date'] ?? null) ?: now()->toDateString();
            $from = $to = $single;
        }

        $q = $this->pin(StudentAttendance::query(), $orgId);
        if ($from) {
            $q->whereDate('attendance_date', '>=', $from);
        }
        if ($to) {
            $q->whereDate('attendance_date', '<=', $to);
        }

        $std = $this->standardId($a['standard'] ?? null, $orgId);
        if ($std) {
            $ids = $this->pin(StudentDetail::query(), $orgId)->where('standard_id', $std)->pluck('id');
            $q->whereIn('student_detail_id', $ids);
        }

        $counts = (clone $q)->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');

        $present = (int) ($counts[1] ?? 0);
        $absent  = (int) ($counts[0] ?? 0);
        $half    = (int) ($counts[2] ?? 0);
        $holiday = (int) ($counts[3] ?? 0);
        $marked  = $present + $absent + $half;

        $out = [
            'covers'       => $this->coverage($orgId),
            'reads'        => 'students only — teacher and employee attendance is a different tool',
            'period'       => ['from' => $from, 'to' => $to],
            'class'        => $std ? Standard::find($std)?->name : 'all classes',
            'present'      => $present,
            'absent'       => $absent,
            'half_day'     => $half,
            'holiday'      => $holiday,
            'marked_total' => $marked,
            'present_pct'  => $marked > 0 ? round(($present + $half * 0.5) / $marked * 100, 1) : null,
            'note'         => $marked === 0
                ? 'Student attendance was never marked for this period. That is NOT the same as everybody being absent, and it says nothing about teacher attendance.'
                : null,
        ];

        // For a single day of one school, say which classes were left unmarked:
        // "attendance is done" is usually asked class by class.
        if ($orgId && ! $std && $from && $from === $to) {
            $day = $this->studentAttendanceOfDay($orgId, $from);

            $out['classes_marked']     = $day['classes_marked'] ?? null;
            $out['classes_not_marked'] = $day['classes_not_marked'] ?? null;
            $out['students_total']     = $day['students_total'] ?? null;
        }

        return $this->clean($out);
    }

    // ══════════════════════════════════════════════════════════════════
    // Exams — marks and datesheet
    // ══════════════════════════════════════════════════════════════════

    /**
     * Marks, read exactly the way the Performance screen reads them: one
     * exam_copies row per student per subject. With no subject named the
     * subjects are added up per student and the list is ranked by marks, so
     * "top 3" is just a limit. An absent row stays a row but adds no marks —
     * reporting it as a zero would libel the student.
     */
    private function examResults(array $a): array
    {
        $orgId = $this->effectiveOrganizationId($a);
        $cross = $this->spansSchools($orgId);
        $limit = $this->limit($a, 20);

        $stdId = $this->standardId($a['standard'] ?? null, $orgId);
        $secId = $this->sectionId($a['section'] ?? null, $stdId, $orgId);
        $subId = $this->subjectId($a['subject'] ?? null, $orgId);
        $stuId = $this->studentRefId($a['student'] ?? null, $orgId);

        $base = $this->pin(ExamCopy::query(), $orgId);
        if ($stdId) {
            $base->where('standard_id', $stdId);
        }
        if ($secId) {
            $base->where('section_id', $secId);
        }
        if ($subId) {
            $base->where('subject_id', $subId);
        }
        if ($stuId) {
            $base->where('student_detail_id', $stuId);
        }

        // No exam named: answer about the latest exam that actually has marks
        // for this selection, rather than blending several exams into one list.
        $examId = $this->examId($a['exam'] ?? null, $orgId) ?: (clone $base)->max('exam_id');
        if ($examId) {
            $base->where('exam_id', $examId);
        }

        $exam = $examId ? Exam::find($examId) : null;

        $head = $this->clean([
            'covers'  => $this->coverage($orgId),
            'exam'    => $exam->exam_name ?? null,
            'term'    => $exam->term ?? null,
            'class'   => $stdId ? Standard::find($stdId)?->name : null,
            'section' => $secId ? Section::find($secId)?->name : null,
            'subject' => $subId ? Subject::find($subId)?->name : ($stuId || $stdId ? 'all subjects added up' : null),
        ]);

        $rows = (clone $base)
            ->with(array_values(array_filter([
                'studentDetail:id,full_name,admission_no,roll_no,standard_id,section_id',
                'studentDetail.standard:id,name',
                'studentDetail.section:id,name',
                'subject:id,name',
                $cross ? 'organization:id,name' : null,
            ])))
            ->limit(2000)
            ->get();

        if ($rows->isEmpty()) {
            return $head + [
                'students' => [],
                'note'     => 'No exam marks have been entered for this selection yet.',
            ];
        }

        $grading = app(\App\Services\GradingService::class);

        $totals = [];
        foreach ($rows as $r) {
            $sid = $r->student_detail_id;
            $totals[$sid] ??= [
                'school'       => $cross ? ($r->organization->name ?? null) : null,
                'name'         => $r->studentDetail->full_name ?? null,
                'roll_no'      => $r->studentDetail->roll_no ?? null,
                'admission_no' => $r->studentDetail->admission_no ?? null,
                'class'        => $r->studentDetail->standard->name ?? null,
                'section'      => $r->studentDetail->section->name ?? null,
                'obtained'     => 0.0,
                'max'          => 0.0,
                'subjects'     => [],
                'absent_in'    => 0,
            ];

            $absent = (bool) $r->is_absent;
            $totals[$sid]['max'] += (float) $r->max_marks;
            if ($absent) {
                $totals[$sid]['absent_in']++;
            } else {
                $totals[$sid]['obtained'] += (float) $r->marks_obtained;
            }

            $totals[$sid]['subjects'][] = $this->clean([
                'subject'  => $r->subject->name ?? null,
                'obtained' => $absent ? null : round((float) $r->marks_obtained, 2),
                'max'      => round((float) $r->max_marks, 2),
                'absent'   => $absent ?: null,
            ]);
        }

        foreach ($totals as &$t) {
            $t['obtained']   = round($t['obtained'], 2);
            $t['max']        = round($t['max'], 2);
            $t['percentage'] = $t['max'] > 0 ? round($t['obtained'] / $t['max'] * 100, 2) : null;
            $t['grade']      = $t['percentage'] !== null ? ($grading->gradeLetter((float) $t['percentage']) ?: null) : null;
            // Absent in everything: no percentage to speak of, just say so.
            if ($t['absent_in'] > 0 && $t['obtained'] == 0.0) {
                $t['grade'] = 'AB';
            }
        }
        unset($t);

        // Best first, percentage breaking ties — the Performance screen's order.
        uasort($totals, function ($x, $y) {
            $byMarks = $y['obtained'] <=> $x['obtained'];

            return $byMarks !== 0 ? $byMarks : (($y['percentage'] ?? 0) <=> ($x['percentage'] ?? 0));
        });

        $scored  = array_values(array_filter($totals, fn ($t) => $t['absent_in'] === 0 || $t['obtained'] > 0));
        $average = $scored !== []
            ? round(array_sum(array_map(fn ($t) => (float) ($t['percentage'] ?? 0), $scored)) / count($scored), 2)
            : null;

        $single = $stuId !== null || count($totals) === 1;

        $list = [];
        $rank = 1;
        foreach ($totals as $t) {
            if (count($list) >= $limit) {
                break;
            }
            $row = $this->clean([
                'rank'         => $rank++,
                'school'       => $t['school'],
                'name'         => $t['name'],
                'roll_no'      => $t['roll_no'],
                'admission_no' => $t['admission_no'],
                'class'        => $t['class'],
                'section'      => $t['section'],
                'obtained'     => $t['obtained'],
                'max'          => $t['max'],
                'percentage'   => $t['percentage'],
                'grade'        => $t['grade'],
                'absent_in'    => $t['absent_in'] ?: null,
                // Subject-by-subject only for one student: a whole class of
                // breakdowns is more rows than any answer needs.
                'subjects'     => $single ? $t['subjects'] : null,
            ]);
            $list[] = $row;
        }

        $absentees = count($totals) - count($scored);

        return $head + $this->clean([
            'students_with_marks' => count($scored),
            'students_listed'     => count($list),
            'class_average_pct'   => $average,
            'highest_marks'       => $scored !== [] ? $scored[0]['obtained'] : null,
            'topper'              => $scored !== [] ? $scored[0]['name'] : null,
            'students_absent'     => $absentees ?: null,
            'students'            => $list,
            'note'                => $scored === []
                ? 'Marks exist for this selection but every student is marked absent.'
                : null,
        ]);
    }

    /** The datesheet: subject, date and time, per class. */
    private function examSchedule(array $a): array
    {
        $orgId = $this->effectiveOrganizationId($a);
        $limit = $this->limit($a, 20);

        $stdId = $this->standardId($a['standard'] ?? null, $orgId);
        $secId = $this->sectionId($a['section'] ?? null, $stdId, $orgId);

        $q = $this->pin(ExamDatesheet::query(), $orgId)
            ->with(['exam:id,exam_name,term', 'standard:id,name', 'section:id,name', 'papers.subject:id,name']);

        if ($stdId) {
            $q->where('standard_id', $stdId);
        }
        if ($secId) {
            $q->where('section_id', $secId);
        }
        if ($examId = $this->examId($a['exam'] ?? null, $orgId)) {
            $q->where('exam_id', $examId);
        }

        $sheets = $q->orderByDesc('id')->limit(10)->get();

        $papers = [];
        foreach ($sheets as $sheet) {
            foreach ($sheet->papers as $paper) {
                if (count($papers) >= $limit) {
                    break 2;
                }
                $papers[] = $this->clean([
                    'exam'    => $sheet->exam->exam_name ?? null,
                    'class'   => $sheet->standard->name ?? null,
                    'section' => $sheet->section->name ?? null,
                    'subject' => $paper->subject->name ?? null,
                    'date'    => $paper->exam_date ? Carbon::parse($paper->exam_date)->toDateString() : null,
                    'from'    => $paper->start_time,
                    'to'      => $paper->end_time,
                    'shift'   => $paper->shift,
                ]);
            }
        }

        // Chronological, because a datesheet is read forwards.
        usort($papers, fn ($x, $y) => ($x['date'] ?? '') <=> ($y['date'] ?? ''));

        return $this->clean([
            'covers' => $this->coverage($orgId),
            'class'  => $stdId ? Standard::find($stdId)?->name : null,
            'papers' => $papers,
            'note'   => $papers === [] ? 'No datesheet has been published for this selection.' : null,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // Staff — attendance and payroll
    // ══════════════════════════════════════════════════════════════════

    private function staffAttendance(array $a): array
    {
        $orgId = $this->effectiveOrganizationId($a);
        $limit = $this->limit($a, 20);
        $type  = in_array($a['type'] ?? 'any', ['teacher', 'employee', 'any'], true) ? ($a['type'] ?? 'any') : 'any';

        [$from, $to] = $this->range($a);
        if (! $from && ! $to) {
            $from = $to = $this->date($a['date'] ?? null) ?: now()->toDateString();
        }

        $out = [
            'covers' => $this->coverage($orgId),
            'period' => ['from' => $from, 'to' => $to],
        ];

        if ($type !== 'employee') {
            $q = $this->pin(TeacherAttendance::query(), $orgId);
            if ($from) {
                $q->whereDate('attendance_date', '>=', $from);
            }
            if ($to) {
                $q->whereDate('attendance_date', '<=', $to);
            }

            $out['teachers'] = $this->tallyAttendance(
                (clone $q)->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status')->all()
            );
            $out['teachers']['marked']         = array_sum($out['teachers']) > 0;
            $out['teachers']['teachers_total'] = $this->pin(TeacherDetail::query(), $orgId)->count();
            $out['teachers_absent'] = (clone $q)->where('status', 0)
                ->with('teacherDetail.user:id,name')
                ->limit($limit)->get()
                ->map(fn ($r) => $this->clean([
                    'name' => $r->teacherDetail->user->name ?? null,
                    'date' => optional($r->attendance_date)->toDateString(),
                ]))->values()->all();
        }

        if ($type !== 'teacher') {
            $q = $this->pin(AdminAttendance::query(), $orgId);
            if ($from) {
                $q->whereDate('date', '>=', $from);
            }
            if ($to) {
                $q->whereDate('date', '<=', $to);
            }

            $out['employees'] = $this->tallyAttendance(
                (clone $q)->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status')->all()
            );
            $out['employees']['marked']          = array_sum($out['employees']) > 0;
            $out['employees']['employees_total'] = $this->pin(AdminEmployee::query(), $orgId)->count();
            $out['employees_absent'] = (clone $q)->whereIn('status', [0, 'absent'])
                ->with('employee:id,name')
                ->limit($limit)->get()
                ->map(fn ($r) => $this->clean([
                    'name' => $r->employee->name ?? null,
                    'date' => $r->date ? Carbon::parse($r->date)->toDateString() : null,
                ]))->values()->all();
        }

        // Read the flags the two blocks already set — `teachers_total` is an
        // int in the same array, so summing the array would count staff who
        // exist as staff who were marked.
        $anyMarked = ($out['teachers']['marked'] ?? false) || ($out['employees']['marked'] ?? false);

        if (! $anyMarked) {
            $out['note'] = 'Staff attendance was never marked for this period — which is not the same as everybody being absent. Say it was not marked.';
        }

        return $out;
    }

    private function payrollReport(array $a): array
    {
        $orgId = $this->effectiveOrganizationId($a);
        $cross = $this->spansSchools($orgId);
        $limit = $this->limit($a, 20);

        $q = $this->pin(AdminSalaryPayment::query(), $orgId);

        if ($month = $this->text($a['month'] ?? null)) {
            $q->where('month', 'like', substr($month, 0, 7) . '%');
        }

        [$from, $to] = $this->range($a);
        if ($from) {
            $q->whereDate('payment_date', '>=', $from);
        }
        if ($to) {
            $q->whereDate('payment_date', '<=', $to);
        }

        $status = $a['status'] ?? 'any';
        if (in_array($status, ['paid', 'pending'], true)) {
            $q->where('status', $status);
        }

        if ($who = $this->text($a['employee'] ?? null)) {
            $q->whereHas('employee', fn ($w) => $w->where('name', 'like', "%{$who}%"));
        }

        $byStatus = (clone $q)->selectRaw('status, COUNT(*) c, SUM(amount) total')
            ->groupBy('status')->get()
            ->mapWithKeys(fn ($r) => [(string) ($r->status ?: 'unknown') => [
                'count' => (int) $r->c,
                'total' => round((float) $r->total, 2),
            ]])->all();

        $rows = (clone $q)
            ->with(array_values(array_filter(['employee:id,name,organization_id', $cross ? 'organization:id,name' : null])))
            ->orderByDesc('payment_date')->orderByDesc('id')
            ->limit($limit)->get()
            ->map(fn ($p) => $this->clean([
                'school'   => $cross ? ($p->organization->name ?? null) : null,
                'employee' => $p->employee->name ?? null,
                'month'    => $p->month,
                'amount'   => round((float) $p->amount, 2),
                'status'   => $p->status,
                'mode'     => $p->payment_mode,
                'paid_on'  => $p->payment_date ? Carbon::parse($p->payment_date)->toDateString() : null,
            ]))->all();

        return $this->clean([
            'covers'      => $this->coverage($orgId),
            'total_rows'  => (clone $q)->count(),
            'total_amount'=> round((float) (clone $q)->sum('amount'), 2),
            'by_status'   => $byStatus,
            'payments'    => $rows,
            'note'        => $rows === [] ? 'No salary payments match this selection.' : null,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // Ledger and timetable
    // ══════════════════════════════════════════════════════════════════

    private function ledgerReport(array $a): array
    {
        $orgId = $this->effectiveOrganizationId($a);
        $cross = $this->spansSchools($orgId);
        $limit = $this->limit($a, 20);

        $q = $this->pin(LedgerTransaction::query(), $orgId);

        [$from, $to] = $this->range($a);
        if ($from) {
            $q->whereDate('txn_date', '>=', $from);
        }
        if ($to) {
            $q->whereDate('txn_date', '<=', $to);
        }

        $type = $a['type'] ?? 'any';
        if (in_array($type, ['credit', 'expense'], true)) {
            $q->where('type', $type);
        }

        if ($party = $this->text($a['party'] ?? null)) {
            // `party` and `reason` are the only text columns this table has —
            // naming a `party_to` that does not exist failed the whole query,
            // and the model reported the ledger as unreadable.
            $q->where(fn ($w) => $w
                ->where('party', 'like', "%{$party}%")
                ->orWhere('reason', 'like', "%{$party}%"));
        }

        $credit  = (float) (clone $q)->where('type', 'credit')->sum('amount');
        $expense = (float) (clone $q)->where('type', 'expense')->sum('amount');

        $rows = (clone $q)
            ->with(array_values(array_filter([$cross ? 'organization:id,name' : null])))
            ->orderByDesc('txn_date')->orderByDesc('id')
            ->limit($limit)->get()
            ->map(fn ($t) => $this->clean([
                'school' => $cross ? ($t->organization->name ?? null) : null,
                'date'   => optional($t->txn_date)->toDateString(),
                'type'   => $t->type,
                'amount' => round((float) $t->amount, 2),
                'party'  => $t->party,
                'reason' => $t->reason,
            ]))->all();

        return $this->clean([
            'covers'        => $this->coverage($orgId),
            'period'        => ['from' => $from, 'to' => $to],
            'money_in'      => round($credit, 2),
            'money_out'     => round($expense, 2),
            'net'           => round($credit - $expense, 2),
            'entry_count'   => (clone $q)->count(),
            'entries'       => $rows,
            'note'          => $rows === [] ? 'No ledger entries match this selection.' : null,
        ]);
    }

    private function classTimetable(array $a): array
    {
        $orgId = $this->effectiveOrganizationId($a);
        $limit = $this->limit($a, 40);

        $stdId = $this->standardId($a['standard'] ?? null, $orgId);
        $secId = $this->sectionId($a['section'] ?? null, $stdId, $orgId);

        $q = $this->pin(TeacherTimeTable::query(), $orgId)
            ->with(['teacher.user:id,name', 'standard:id,name', 'section:id,name', 'subject:id,name']);

        if ($stdId) {
            $q->where('standard_id', $stdId);
        }
        if ($secId) {
            $q->where('section_id', $secId);
        }
        if ($teacher = $this->text($a['teacher'] ?? null)) {
            $q->whereHas('teacher.user', fn ($w) => $w->where('name', 'like', "%{$teacher}%"));
        }
        // day_of_week is stored 1..6 (Mon..Sat), so a day NAME has to be
        // translated before it can match anything.
        if ($day = $this->text($a['day'] ?? null)) {
            $number = null;
            foreach (self::WEEKDAYS as $n => $label) {
                if (strcasecmp($label, $day) === 0 || stripos($label, $day) === 0) {
                    $number = $n;
                    break;
                }
            }

            $number ? $q->where('day_of_week', $number) : $q->where('day_of_week', 'like', "%{$day}%");
        }

        $periods = $q->orderBy('day_of_week')->orderBy('start_time')
            ->limit($limit)->get()
            ->map(fn ($p) => $this->clean([
                'day'     => self::WEEKDAYS[(int) $p->day_of_week] ?? $p->day_of_week,
                'from'    => $p->start_time,
                'to'      => $p->end_time,
                'class'   => $p->standard->name ?? null,
                'section' => $p->section->name ?? null,
                'subject' => $p->subject->name ?? null,
                'teacher' => $p->teacher->user->name ?? null,
            ]))->all();

        return $this->clean([
            'covers'  => $this->coverage($orgId),
            'class'   => $stdId ? Standard::find($stdId)?->name : null,
            'section' => $secId ? Section::find($secId)?->name : null,
            'periods' => $periods,
            'note'    => $periods === [] ? 'No timetable periods are set for this selection.' : null,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // The general surface — any record type in LmsDataMap
    // ══════════════════════════════════════════════════════════════════

    /** Ids that mean something to a person, and what to call them in a row. */
    private const LABEL_ALIAS = [
        'standard_id'       => 'class',
        'section_id'        => 'section',
        'subject_id'        => 'subject',
        'student_detail_id' => 'student',
        'exam_id'           => 'exam',
        'organization_id'   => 'school',
        'user_id'           => 'user',
        'admin_employee_id' => 'employee',
        'transportation_id' => 'route',
    ];

    /** Columns that are noise in a listing unless they were asked for. */
    private const DULL_COLUMNS = ['created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'email_verified_at'];

    private function describeData(array $a): array
    {
        $map = LmsDataMap::forScope($this->scope);
        $key = $this->text($a['entity'] ?? null);

        if (! $key) {
            return [
                'covers'  => $this->coverage($this->effectiveOrganizationId($a)),
                'records' => array_map(
                    fn (string $name) => ['entity' => $name, 'about' => $map[$name]['label']],
                    array_keys($map),
                ),
                'how'     => 'Pick one and call describe_data again with it to see its field names, then query_records to read rows.',
            ];
        }

        if (! isset($map[$key])) {
            return ['error' => 'No such record type.', 'available' => array_keys($map)];
        }

        $entity  = $map[$key];
        $columns = LmsDataMap::columns($entity['model']);

        if ($columns === []) {
            return ['error' => 'That module is not set up on this installation, so there is nothing to read.'];
        }

        $orgId = $this->effectiveOrganizationId($a);
        $query = $this->scopedQuery($entity, $orgId);

        if (! $query) {
            return ['error' => 'That record type cannot be read from this panel.'];
        }

        return $this->clean([
            'covers'       => $this->coverage($orgId),
            'entity'       => $key,
            'about'        => $entity['label'],
            'fields'       => array_merge($columns, $this->relatedFieldNames($entity)),
            'searchable'   => array_values(array_intersect($entity['search'], $columns)),
            'rows_visible' => $query->count(),
            'note'         => $this->relatedFieldNames($entity)
                ? 'Dotted names are fields of a linked record; ask for them like any other field, in `fields` or in a filter.'
                : null,
            'same_as'      => $entity['aliases'] ?? null,
        ]);
    }

    private function queryRecords(array $a): array
    {
        $map = LmsDataMap::forScope($this->scope);
        $key = $this->text($a['entity'] ?? null);

        if (! $key || ! isset($map[$key])) {
            return ['error' => 'No such record type.', 'available' => array_keys($map)];
        }

        $entity  = $map[$key];
        $columns = LmsDataMap::columns($entity['model']);
        $orgId   = $this->effectiveOrganizationId($a);

        if ($columns === []) {
            return ['error' => 'That module is not set up on this installation, so there is nothing to read.'];
        }

        $query = $this->scopedQuery($entity, $orgId);

        if (! $query) {
            return ['error' => 'That record type cannot be read from this panel.'];
        }

        [$applied, $rejected] = $this->applyWhere($query, (array) ($a['where'] ?? []), $columns, $orgId, $entity);
        $this->applySearch($query, $a['search'] ?? null, $entity, $columns);
        $this->narrowByClass($query, $a, $columns, $orgId);

        $total = (clone $query)->count();

        $head = $this->clean([
            'covers'        => $this->coverage($orgId),
            'entity'        => $key,
            'matching_rows' => $total,
            'filters'       => $applied ?: null,
            'ignored'       => $rejected ?: null,
        ]);

        if (filter_var($a['count_only'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return $head;
        }

        $asked = (array) ($a['fields'] ?? []);

        // "*" and all_fields both mean the whole record. Asked for full details,
        // the assistant used to hand back the same dozen default columns and
        // call it complete; now it returns every column it is allowed to read.
        $everything = filter_var($a['all_fields'] ?? false, FILTER_VALIDATE_BOOLEAN)
            || in_array('*', array_map('strval', $asked), true)
            || in_array('all', array_map('strtolower', array_map('strval', $asked)), true);

        $asked = array_map(fn ($f) => $this->resolveField((string) $f, $entity), $asked);

        $fields = array_values(array_intersect($asked, $columns));
        $wanted = array_values(array_intersect($asked, $this->relatedFieldNames($entity)));

        if ($everything) {
            $fields = $columns;
            $wanted = $this->relatedFieldNames($entity);
        } elseif ($fields === [] && $wanted === []) {
            $fields = $this->defaultFields($columns, $entity);
            $wanted = $this->relatedFieldNames($entity);
        }

        // Ids the row needs for its labels have to be selected even when the
        // caller did not ask for them.
        $select = array_values(array_unique(array_merge($fields, array_values(array_intersect(array_keys(self::LABEL_ALIAS), $columns)))));

        $asks      = $this->text($a['order_by'] ?? null);
        $asks      = $asks ? $this->resolveField($asks, $entity) : null;
        $order     = in_array($asks, $columns, true) ? $asks : (in_array('id', $columns, true) ? 'id' : $columns[0]);
        $direction = strtolower((string) ($a['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        // A related field is loaded through its relation and flattened into the
        // row as `user_name`, `user_image` … — one query for the lot, not one
        // per row.
        $loads = [];
        foreach ($this->groupRelatedFields($wanted) as $relation => $subFields) {
            $loads[$relation] = fn ($q) => $q->select(array_values(array_unique(array_merge(['id'], $subFields))));
        }

        $models = $query->with($loads)
            ->orderBy($order, $direction)
            ->limit($everything ? min($this->limit($a, 20), 40) : $this->limit($a, 20))
            ->get($select);

        $rows = $models->map(function (Model $model) use ($wanted) {
            $row = $model->getAttributes();

            foreach ($this->groupRelatedFields($wanted) as $relation => $subFields) {
                $related = $model->relationLoaded($relation) ? $model->getRelation($relation) : null;
                foreach ($subFields as $field) {
                    $row[$relation . '_' . $field] = $related?->{$field};
                }
            }

            return $row;
        })->all();

        return $head + $this->clean([
            'fields' => array_merge($fields, $wanted),
            'rows'   => $this->labelRows($rows, $fields),
            'note'   => match (true) {
                $rows === [] => 'Nothing matches this filter.',
                // Say it, so the model does not report a default selection as
                // the whole record when the user asked for everything.
                ! $everything => 'These are the default columns. For every field of the record, ask again with all_fields true.',
                default => null,
            },
        ]);
    }

    private function aggregateRecords(array $a): array
    {
        $map = LmsDataMap::forScope($this->scope);
        $key = $this->text($a['entity'] ?? null);

        if (! $key || ! isset($map[$key])) {
            return ['error' => 'No such record type.', 'available' => array_keys($map)];
        }

        $entity  = $map[$key];
        $columns = LmsDataMap::columns($entity['model']);
        $orgId   = $this->effectiveOrganizationId($a);

        if ($columns === []) {
            return ['error' => 'That module is not set up on this installation, so there is nothing to read.'];
        }

        $query = $this->scopedQuery($entity, $orgId);

        if (! $query) {
            return ['error' => 'That record type cannot be read from this panel.'];
        }

        $metric = in_array($a['metric'] ?? 'count', ['count', 'sum', 'avg', 'min', 'max'], true) ? ($a['metric'] ?? 'count') : 'count';
        $askField = $this->text($a['field'] ?? null);
        $askGroup = $this->text($a['group_by'] ?? null);
        $askField = $askField ? $this->resolveField($askField, $entity) : null;
        $askGroup = $askGroup ? $this->resolveField($askGroup, $entity) : null;

        $field  = in_array($askField, $columns, true) ? $askField : null;
        $group  = in_array($askGroup, $columns, true) ? $askGroup : null;

        if ($metric !== 'count' && ! $field) {
            return ['error' => 'That calculation needs a numeric field that exists on this record type.'];
        }

        [$applied, $rejected] = $this->applyWhere($query, (array) ($a['where'] ?? []), $columns, $orgId, $entity);
        $this->applySearch($query, $a['search'] ?? null, $entity, $columns);
        $this->narrowByClass($query, $a, $columns, $orgId);

        $expression = $metric === 'count' ? 'COUNT(*)' : strtoupper($metric) . '(`' . $field . '`)';

        $head = $this->clean([
            'covers'  => $this->coverage($orgId),
            'entity'  => $key,
            'metric'  => $metric . ($field ? " of {$field}" : ''),
            'filters' => $applied ?: null,
            'ignored' => $rejected ?: null,
        ]);

        if (! $group) {
            $value = (clone $query)->selectRaw($expression . ' as value')->value('value');

            return $head + ['value' => $this->number($value)];
        }

        $rows = (clone $query)
            ->selectRaw('`' . $group . '` as bucket, ' . $expression . ' as value')
            ->groupBy($group)
            ->orderByDesc('value')
            ->limit(self::MAX_ROWS)
            ->get()
            ->map(fn ($row) => ['group' => $row->bucket, 'value' => $this->number($row->value)])
            ->all();

        return $head + [
            'grouped_by' => self::LABEL_ALIAS[$group] ?? $group,
            'groups'     => $this->labelGroups($rows, $group),
            'note'       => $rows === [] ? 'Nothing matches this filter.' : null,
        ];
    }

    // ── plumbing for the general surface ──────────────────────────────

    /**
     * A query for one entity, already pinned to the caller's school — or null
     * when it cannot be pinned, which is the only safe answer: a table with no
     * organization of its own is reached through its parent, and if there is no
     * parent either, a school user does not get to read it at all.
     */
    private function scopedQuery(array $entity, ?int $orgId): ?Builder
    {
        /** @var Model $model */
        $model   = new $entity['model'];
        $columns = LmsDataMap::columns($entity['model']);
        $query   = $model->newQuery();

        if (! $orgId) {
            return $query;
        }

        if (in_array('organization_id', $columns, true)) {
            return $query->where($model->getTable() . '.organization_id', $orgId);
        }

        // Declared parent first — some tables hang off the school profile or a
        // seating plan rather than off anything with a class on it.
        $parents = [];
        if (! empty($entity['pin_via'])) {
            $parents[$entity['pin_via']['column']] = $entity['pin_via']['model'];
        }
        $parents += ['student_detail_id' => StudentDetail::class, 'standard_id' => Standard::class, 'section_id' => Section::class];

        foreach ($parents as $column => $parent) {
            if (in_array($column, $columns, true)) {
                return $query->whereIn($column, (new $parent)->newQuery()->where('organization_id', $orgId)->select('id'));
            }
        }

        return null;
    }

    /**
     * @param  array<int,mixed>  $clauses
     * @param  array<int,string>  $columns
     * @return array{0:array<int,string>,1:array<int,string>}
     */
    private function applyWhere(Builder $query, array $clauses, array $columns, ?int $orgId = null, array $entity = []): array
    {
        $applied  = $rejected = [];
        $related  = $this->relatedFieldNames($entity);

        foreach (array_slice($clauses, 0, 8) as $clause) {
            $clause = (array) $clause;
            $field  = $this->text($clause['field'] ?? null);
            $op     = strtolower((string) ($clause['op'] ?? 'eq'));
            $value  = $clause['value'] ?? null;
            $value  = is_scalar($value) ? (string) $value : null;

            $field = $field ? $this->resolveField($field, $entity) : $field;

            // `user.image` and friends — filter inside the linked record.
            if ($field && str_contains($field, '.') && in_array($field, $related, true)) {
                [$relation, $sub] = explode('.', $field, 2);

                $query->whereHas($relation, function ($q) use ($sub, $op, $value) {
                    $this->applyOp($q, $sub, $op, $value);
                });

                $applied[] = $field . ' ' . $op . ' ' . (string) $value;
                continue;
            }

            if (! $field || ! in_array($field, $columns, true)) {
                $rejected[] = 'no such field: ' . ($field ?: '(blank)');
                continue;
            }

            // "standard_id is NURSERY" — the model should never have to know an
            // id to ask a question a person would ask by name.
            if ($value !== null && ! is_numeric($value) && isset(LmsDataMap::LABELS[$field]) && in_array($op, ['eq', 'ne', 'in', 'like'], true)) {
                $ids = $this->idsNamed($field, $value, $orgId);

                if ($ids === []) {
                    // Nothing carries that name, so nothing matches. Dropping
                    // the filter instead would answer about the whole school
                    // and look like a real count.
                    $query->whereRaw('1 = 0');
                    $applied[] = $field . ' is ' . $value . ' (no such ' . (self::LABEL_ALIAS[$field] ?? $field) . ' exists)';
                    continue;
                }

                $op === 'ne' ? $query->whereNotIn($field, $ids) : $query->whereIn($field, $ids);
                $applied[] = $field . ' ' . ($op === 'ne' ? 'is not ' : 'is ') . $value;
                continue;
            }

            if (! $this->applyOp($query, $field, $op, $value)) {
                $rejected[] = 'unknown comparison: ' . $op;
                continue;
            }

            $applied[] = trim($field . ' ' . $op . ' ' . (string) $value);
        }

        return [$applied, $rejected];
    }

    /** One comparison, on a query that may be the main one or a relation's. */
    private function applyOp($query, string $field, string $op, ?string $value): bool
    {
        match ($op) {
            'eq'        => $query->where($field, $value),
            'ne'        => $query->where($field, '!=', $value),
            'gt'        => $query->where($field, '>', $value),
            'gte'       => $query->where($field, '>=', $value),
            'lt'        => $query->where($field, '<', $value),
            'lte'       => $query->where($field, '<=', $value),
            'like'      => $query->where($field, 'like', '%' . $value . '%'),
            'in'        => $query->whereIn($field, array_map('trim', explode(',', (string) $value))),
            'is_null'   => $query->whereNull($field),
            'not_null'  => $query->whereNotNull($field),
            // "has a photo" is not the same as "the column is not null": an
            // empty string is how this app records "nothing uploaded".
            'empty'     => $query->where(fn ($w) => $w->whereNull($field)->orWhere($field, '')),
            'not_empty' => $query->whereNotNull($field)->where($field, '!=', ''),
            default     => null,
        };

        return in_array($op, ['eq', 'ne', 'gt', 'gte', 'lt', 'lte', 'like', 'in', 'is_null', 'not_null', 'empty', 'not_empty'], true);
    }

    /**
     * The real field behind a name the model used. A student's photo lives on
     * the login, not the student row, so "image" has to mean `user.image` here
     * — otherwise the panel shows a photo and the assistant swears there is
     * none.
     */
    private function resolveField(string $field, array $entity): string
    {
        if (isset($entity['aliases'][$field])) {
            return $entity['aliases'][$field];
        }

        // "class", "section", "subject", "student", "school" are what a person
        // calls the id columns, and what rows come back labelled as — so they
        // have to work as field names too.
        return array_flip(self::LABEL_ALIAS)[$field] ?? $field;
    }

    /**
     * Field names an entity exposes through a linked record, as `user.image`.
     *
     * @return array<int,string>
     */
    private function relatedFieldNames(array $entity): array
    {
        $names = [];

        foreach ($entity['related'] ?? [] as $relation => $spec) {
            $allowed = LmsDataMap::columns($spec['model']);

            foreach ($spec['fields'] as $field) {
                if (in_array($field, $allowed, true)) {
                    $names[] = $relation . '.' . $field;
                }
            }
        }

        return $names;
    }

    /**
     * @param  array<int,string>  $dotted
     * @return array<string,array<int,string>>
     */
    private function groupRelatedFields(array $dotted): array
    {
        $grouped = [];

        foreach ($dotted as $name) {
            [$relation, $field] = explode('.', $name, 2);
            $grouped[$relation][] = $field;
        }

        return $grouped;
    }

    /**
     * The ids of the rows a label names — "NURSERY" for standard_id, a student
     * name for student_detail_id — pinned to the caller's school where the
     * target carries one.
     *
     * @return array<int,int>
     */
    private function idsNamed(string $field, string $value, ?int $orgId): array
    {
        $target = LmsDataMap::LABELS[$field];
        $query  = $target['model']::query();

        if ($orgId && in_array('organization_id', LmsDataMap::columns($target['model']), true)) {
            $query->where('organization_id', $orgId);
        }

        return $query
            ->where(fn ($w) => $w
                ->where($target['column'], $value)
                ->orWhere($target['column'], 'like', "%{$value}%"))
            ->limit(200)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** `standard` / `section` arguments, by name, on any entity that has them. */
    private function narrowByClass(Builder $query, array $a, array $columns, ?int $orgId): void
    {
        $standardId = null;

        if (in_array('standard_id', $columns, true)) {
            $standardId = $this->standardId($a['standard'] ?? null, $orgId);
            if ($standardId) {
                $query->where('standard_id', $standardId);
            }
        }

        if (in_array('section_id', $columns, true)) {
            $sectionId = $this->sectionId($a['section'] ?? null, $standardId, $orgId);
            if ($sectionId) {
                $query->where('section_id', $sectionId);
            }
        }
    }

    private function applySearch(Builder $query, mixed $text, array $entity, array $columns): void
    {
        $text = $this->text($text);
        if (! $text) {
            return;
        }

        $searchable = array_values(array_intersect($entity['search'] ?? [], $columns));
        if ($searchable === []) {
            return;
        }

        $query->where(function ($w) use ($searchable, $text) {
            foreach ($searchable as $column) {
                $w->orWhere($column, 'like', "%{$text}%");
            }
        });
    }

    /**
     * What to show when the model did not choose: identity first, timestamps
     * last, and never more than a screenful of columns.
     *
     * @param  array<int,string>  $columns
     * @return array<int,string>
     */
    private function defaultFields(array $columns, array $entity): array
    {
        $preferred = array_values(array_intersect(
            array_merge(['id'], $entity['search'] ?? [], array_keys(self::LABEL_ALIAS)),
            $columns,
        ));

        $rest = array_values(array_diff($columns, $preferred, self::DULL_COLUMNS));

        return array_slice(array_merge($preferred, $rest), 0, 12);
    }

    /**
     * Turn the foreign keys in a set of rows into names, in one query per kind
     * rather than one per row.
     *
     * @param  array<int,array<string,mixed>>  $rows
     * @param  array<int,string>  $fields
     * @return array<int,array<string,mixed>>
     */
    private function labelRows(array $rows, array $fields): array
    {
        foreach (LmsDataMap::LABELS as $column => $target) {
            $ids = array_values(array_unique(array_filter(array_column($rows, $column))));
            if ($ids === []) {
                continue;
            }

            $names = $target['model']::query()->whereIn('id', $ids)->pluck($target['column'], 'id');
            $alias = self::LABEL_ALIAS[$column] ?? str_replace('_id', '', $column);

            foreach ($rows as $i => $row) {
                if (! empty($row[$column]) && isset($names[$row[$column]])) {
                    $rows[$i][$alias] = $names[$row[$column]];
                }
                // The raw id is noise once it has a name — unless it was asked for.
                if (! in_array($column, $fields, true)) {
                    unset($rows[$i][$column]);
                }
            }
        }

        return array_map(fn (array $row) => $this->clean($row), $rows);
    }

    /**
     * @param  array<int,array{group:mixed,value:mixed}>  $rows
     * @return array<int,array<string,mixed>>
     */
    private function labelGroups(array $rows, string $column): array
    {
        $target = LmsDataMap::LABELS[$column] ?? null;
        if (! $target) {
            return $rows;
        }

        $ids   = array_values(array_unique(array_filter(array_column($rows, 'group'))));
        $names = $ids === [] ? collect() : $target['model']::query()->whereIn('id', $ids)->pluck($target['column'], 'id');

        return array_map(fn (array $row) => [
            'group' => $names[$row['group']] ?? $row['group'],
            'value' => $row['value'],
        ], $rows);
    }

    private function number(mixed $value): int|float|null
    {
        if ($value === null) {
            return null;
        }

        $number = (float) $value;

        return floor($number) == $number ? (int) $number : round($number, 2);
    }

    // ══════════════════════════════════════════════════════════════════
    // Recent records
    // ══════════════════════════════════════════════════════════════════

    private function recentRecords(array $a): array
    {
        $entity = (string) ($a['entity'] ?? '');
        $limit  = $this->limit($a, 10);
        $orgId  = $this->effectiveOrganizationId($a);

        if (in_array($entity, self::PLATFORM_ENTITIES, true) && ! $this->scope->isPlatform()) {
            return ['error' => 'That record type is not available in this panel.'];
        }

        $covers = ['covers' => $this->coverage($orgId)];

        return $covers + match ($entity) {
            'announcements' => ['announcements' => $this->pin(Announcement::query(), $orgId)
                ->orderByDesc('id')->limit($limit)->get(['announcement_name', 'type', 'created_at'])
                ->map(fn ($r) => ['title' => $r->announcement_name, 'type' => $r->type, 'posted' => optional($r->created_at)->toDateString()])->all()],

            'homework' => ['homework' => $this->pin(HomeWork::query(), $orgId)
                ->with(['standard:id,name', 'subject:id,name'])->orderByDesc('id')->limit($limit)->get()
                ->map(fn ($r) => ['title' => $r->title, 'class' => $r->standard->name ?? null, 'subject' => $r->subject->name ?? null, 'posted' => optional($r->created_at)->toDateString()])->all()],

            'exams' => ['exams' => $this->pin(Exam::query(), $orgId)
                ->orderByDesc('id')->limit($limit)->get(['exam_name', 'term', 'exam_type', 'start_date', 'end_date', 'academic_year'])
                ->map(fn ($r) => ['name' => $r->exam_name, 'term' => $r->term, 'type' => $r->exam_type, 'from' => (string) $r->start_date, 'to' => (string) $r->end_date, 'year' => $r->academic_year])->all()],

            'certificates' => ['certificates' => $this->pin(Certificate::query(), $orgId)
                ->with('student:id,full_name')->orderByDesc('id')->limit($limit)->get()
                ->map(fn ($r) => ['no' => $r->certificate_no, 'type' => $r->type, 'student' => $r->student->full_name ?? null, 'event' => $r->event_name, 'issued' => optional($r->issued_date)->toDateString()])->all()],

            'transfer_certificates' => ['transfer_certificates' => $this->pin(TransferCertificate::query(), $orgId)
                ->with('student:id,full_name')->orderByDesc('id')->limit($limit)->get()
                ->map(fn ($r) => ['no' => $r->tc_no, 'student' => $r->student->full_name ?? null, 'last_class' => $r->last_class_studied, 'issued' => optional($r->issue_date)->toDateString()])->all()],

            'admission_enquiries' => ['admission_enquiries' => $this->pin(AdmissionEnquiry::query(), $orgId)
                ->orderByDesc('id')->limit($limit)->get()
                ->map(fn ($r) => $this->pick($r, ['student_name', 'name', 'parent_name', 'father_name', 'mobile', 'phone', 'email', 'standard_id', 'status', 'created_at']))->all()],

            'ledger' => ['ledger' => $this->pin(LedgerTransaction::query(), $orgId)
                ->orderByDesc('txn_date')->limit($limit)->get(['type', 'amount', 'txn_date', 'party', 'reason'])
                ->map(fn ($r) => ['type' => $r->type, 'amount' => (float) $r->amount, 'date' => (string) $r->txn_date, 'party' => $r->party, 'reason' => $r->reason])->all()],

            'transport' => ['transport_routes' => $this->pin(Transportation::query(), $orgId)
                ->orderBy('route_name')->limit($limit)->get(['route_name', 'pickup_time', 'pickup_location', 'drop_location', 'monthly_fee', 'capacity', 'is_active'])
                ->map(fn ($r) => ['route' => $r->route_name, 'pickup' => $r->pickup_location, 'drop' => $r->drop_location, 'time' => $r->pickup_time, 'monthly_fee' => (float) $r->monthly_fee, 'capacity' => $r->capacity, 'active' => (bool) $r->is_active])->all()],

            'books' => ['books' => $this->pin(Book::query(), $orgId)->orderByDesc('id')->limit($limit)->get()
                ->map(fn ($r) => $this->pick($r, ['name', 'title', 'book_name', 'author', 'isbn', 'quantity', 'available', 'standard_id']))->all()],

            'fee_structures' => ['fee_structures' => $this->pin(FeeStructure::query(), $orgId)
                ->with('standard:id,name')->orderByDesc('id')->limit($limit)->get()
                ->map(fn ($r) => ['class' => $r->standard->name ?? null, 'name' => $r->fee_name, 'amount' => (float) $r->amount, 'type' => $r->fee_type, 'year' => $r->academic_year, 'active' => (bool) $r->is_active])->all()],

            'credit_queries' => ['credit_queries' => $this->pin(CreditQuery::query(), $orgId)->with('organization:id,name')
                ->orderByDesc('id')->limit($limit)->get()
                ->map(fn ($r) => $this->clean([
                    'school'  => $this->spansSchools($orgId) ? ($r->organization->name ?? null) : null,
                    'heading' => $r->heading,
                    'reason'  => $r->reason,
                    'amount'  => (float) $r->amount,
                    'status'  => $r->status,
                    'from'    => optional($r->start_date)->toDateString(),
                    'due_on'  => optional($r->end_date)->toDateString(),
                ]))->all()],

            'support_messages' => ['support_messages' => $this->pin(ContactSuperAdmin::query(), $orgId)->with('organization:id,name')
                ->orderByDesc('id')->limit($limit)->get()
                ->map(fn ($r) => array_merge(['school' => $r->organization->name ?? null], $this->pick($r, ['topic', 'admin_query', 'super_admin_text', 'super_admin_reply', 'created_at'])))->all()],

            'ratings' => ['ratings' => $this->pin(RateLms::query(), $orgId)->with('organization:id,name')
                ->orderByDesc('id')->limit($limit)->get()
                ->map(fn ($r) => array_merge(['school' => $r->organization->name ?? null], $this->pick($r, ['rating', 'stars', 'feedback', 'message', 'created_at'])))->all()],

            'demo_requests' => ['demo_requests' => WebsiteDemo::orderByDesc('id')->limit($limit)->get()
                ->map(fn ($r) => $this->pick($r, ['name', 'school_name', 'email', 'mobile', 'phone', 'city', 'state', 'status', 'created_at']))->all()],

            'schools' => ['schools' => $this->schoolsQuery()->orderByDesc('id')->limit($limit)
                ->get(['id', 'name', 'serial_number', 'status', 'education_board', 'state', 'created_at'])
                ->map(fn ($o) => ['name' => $o->name, 'serial' => $o->serial_number, 'status' => $o->status ? 'active' : 'inactive', 'board' => $o->education_board, 'state' => $o->state, 'added' => optional($o->created_at)->toDateString()])->all()],

            default => ['error' => 'Unknown record type.'],
        };
    }

    // ══════════════════════════════════════════════════════════════════
    // Schools
    // ══════════════════════════════════════════════════════════════════

    /** Organizations this caller may see at all — one, for a pinned reader. */
    private function schoolsQuery(): Builder
    {
        $q = Organization::query();

        return ($pinned = $this->scope->forcedOrganizationId())
            ? $q->whereKey($pinned)
            : $q;
    }

    private function searchSchools(array $a): array
    {
        $q = $this->schoolsQuery();

        if ($text = $this->text($a['query'] ?? null)) {
            $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$text}%")
                ->orWhere('serial_number', 'like', "%{$text}%")
                ->orWhere('email', 'like', "%{$text}%")
                ->orWhere('education_board', 'like', "%{$text}%")
                ->orWhere('city', 'like', "%{$text}%")
                ->orWhere('state', 'like', "%{$text}%"));
        }

        $status = $a['status'] ?? 'any';
        if ($status === 'active') {
            $q->where('status', 1);
        } elseif ($status === 'inactive') {
            $q->where('status', 0);
        }

        return [
            'matched' => (clone $q)->count(),
            'schools' => $q->orderBy('name')->limit($this->limit($a, 20))->get()
                ->map(fn ($o) => [
                    'name'   => $o->name,
                    'serial' => $o->serial_number,
                    'status' => $o->status ? 'active' : 'inactive',
                    'board'  => $o->education_board,
                    'city'   => $o->city,
                    'state'  => $o->state,
                    'email'  => $o->email,
                    'phone'  => $o->mobile_number,
                ])->all(),
        ];
    }

    private function schoolOverview(array $a): array
    {
        // findSchool() is already narrowed to what this caller may see, so a
        // pinned reader asking about someone else's school simply finds nothing.
        $org = $this->findSchool($a['school'] ?? null);

        if (! $org) {
            return ['found' => false, 'message' => 'No school you have access to matched that name or serial number.'];
        }

        return [
            'found'  => true,
            'school' => [
                'name'        => $org->name,
                'serial'      => $org->serial_number,
                'status'      => $org->status ? 'active' : 'inactive',
                'board'       => $org->education_board,
                'school_code' => $org->school_code,
                'udise'       => $org->udise_number,
                'address'     => $org->address,
                'city'        => $org->city,
                'state'       => $org->state,
                'email'       => $org->email,
                'phone'       => $org->mobile_number,
                'since'       => optional($org->created_at)->toDateString(),
            ],
            'counts' => [
                'students'    => StudentDetail::where('organization_id', $org->id)->count(),
                'teachers'    => TeacherDetail::where('organization_id', $org->id)->count(),
                'employees'   => AdminEmployee::where('organization_id', $org->id)->count(),
                'classes'     => Standard::where('organization_id', $org->id)->count(),
                'sections'    => Section::where('organization_id', $org->id)->count(),
                'logins'      => User::where('organization_id', $org->id)->count(),
                'panel_users' => User::where('organization_id', $org->id)
                    ->whereIn('role', ['admin', 'sub-admin', 'accounts'])->count(),
            ],
            'student_fees_collected' => (float) FeePayment::where('organization_id', $org->id)->sum('amount'),
            'platform_fees_paid'     => (float) SuperAdminFeePayment::where('organization_id', $org->id)->sum('amount'),
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    // One day of the school
    // ══════════════════════════════════════════════════════════════════

    /**
     * "Aaj ki summary do."
     *
     * The commonest question there is, and the one the assistant used to be
     * worst at, because answering it from single-subject tools took more tool
     * rounds than a question is allowed. So it is one call: every part of the
     * day this login may read, each of them saying explicitly whether something
     * was recorded or simply never marked.
     */
    private function dailySummary(array $a): array
    {
        $orgId = $this->effectiveOrganizationId($a);
        $date  = $this->date($a['date'] ?? null) ?: now()->toDateString();
        $day   = Carbon::parse($date);

        $out = [
            'covers'   => $this->coverage($orgId),
            'date'     => $date,
            'weekday'  => $day->format('l'),
            'is_today' => $date === now()->toDateString(),
        ];

        if ($this->scope->can('attendance')) {
            $out['student_attendance'] = $this->studentAttendanceOfDay($orgId, $date);
            $out['staff_attendance']   = $this->staffAttendanceOfDay($orgId, $date);
        }

        if ($this->scope->can('fees')) {
            $fees = $this->pin(FeePayment::query(), $orgId)->whereDate('payment_date', $date);
            $count = (clone $fees)->count();

            $out['fee_collected'] = $this->clean([
                'amount'   => round((float) (clone $fees)->sum('amount'), 2),
                'payments' => $count,
                'by_mode'  => (clone $fees)->selectRaw('payment_mode, COUNT(*) c, SUM(amount) total')
                    ->groupBy('payment_mode')->get()
                    ->mapWithKeys(fn ($r) => [($r->payment_mode ?: 'unspecified') => ['count' => (int) $r->c, 'total' => round((float) $r->total, 2)]])->all(),
                'note'     => $count === 0 ? 'No fee was collected on this date.' : null,
            ]);
        }

        if ($this->scope->can('transport')) {
            $transport = $this->pin(TransportFeePayment::query(), $orgId)->whereDate('payment_date', $date);
            $out['transport_fee_collected'] = [
                'amount'   => round((float) (clone $transport)->sum('amount'), 2),
                'payments' => (clone $transport)->count(),
            ];
        }

        if ($this->scope->can('ledger')) {
            $ledger = $this->pin(LedgerTransaction::query(), $orgId)->whereDate('txn_date', $date);
            $in     = round((float) (clone $ledger)->where('type', 'credit')->sum('amount'), 2);
            $outAmt = round((float) (clone $ledger)->where('type', 'expense')->sum('amount'), 2);

            $out['ledger'] = [
                'money_in'  => $in,
                'money_out' => $outAmt,
                'net'       => round($in - $outAmt, 2),
                'entries'   => (clone $ledger)->count(),
            ];
        }

        if ($this->scope->can('payroll')) {
            $salary = $this->pin(AdminSalaryPayment::query(), $orgId)->whereDate('payment_date', $date);
            $out['salary_paid'] = [
                'amount'   => round((float) (clone $salary)->sum('amount'), 2),
                'payments' => (clone $salary)->count(),
            ];
        }

        if ($this->scope->can('students')) {
            $out['new_admissions'] = $this->pin(StudentDetail::query(), $orgId)
                ->whereDate('date_of_admission', $date)->count();

            $out['birthdays'] = $this->pin(StudentDetail::query(), $orgId)
                ->whereNotNull('dob')
                ->whereMonth('dob', $day->month)->whereDay('dob', $day->day)
                ->with('standard:id,name')
                ->limit(20)->get()
                ->map(fn ($s) => $this->clean(['name' => $s->full_name, 'class' => $s->standard->name ?? null]))
                ->all();
        }

        if ($this->scope->can('enquiries')) {
            $out['enquiries_received'] = $this->pin(AdmissionEnquiry::query(), $orgId)
                ->whereDate('created_at', $date)->count();
        }

        if ($this->scope->can('homework')) {
            $homework = $this->pin(HomeWork::query(), $orgId)->whereDate('created_at', $date);
            $out['homework_posted'] = [
                'count' => (clone $homework)->count(),
                'items' => (clone $homework)->with(['standard:id,name', 'subject:id,name'])->limit(10)->get()
                    ->map(fn ($h) => $this->clean([
                        'title'   => $h->title,
                        'class'   => $h->standard->name ?? null,
                        'subject' => $h->subject->name ?? null,
                    ]))->all(),
            ];
        }

        if ($this->scope->can('announcements')) {
            $out['announcements_posted'] = $this->pin(Announcement::query(), $orgId)
                ->whereDate('created_at', $date)
                ->limit(10)->get(['announcement_name', 'type'])
                ->map(fn ($n) => $this->clean(['title' => $n->announcement_name, 'type' => $n->type]))->all();
        }

        if ($this->scope->can('exams')) {
            $out['exams_running'] = $this->pin(Exam::query(), $orgId)
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->limit(10)->get(['exam_name', 'term', 'start_date', 'end_date'])
                ->map(fn ($e) => $this->clean([
                    'name' => $e->exam_name,
                    'term' => $e->term,
                    'from' => (string) $e->start_date,
                    'to'   => (string) $e->end_date,
                ]))->all();
        }

        if ($this->scope->can('calendar')) {
            $out['calendar_events'] = $this->pin(CalendarEvent::query(), $orgId)
                ->whereDate('date', $date)
                ->limit(15)->get(['title', 'event_type', 'start_time', 'end_time', 'is_cancelled'])
                ->map(fn ($e) => $this->clean([
                    'title'     => $e->title,
                    'type'      => $e->event_type,
                    'from'      => $e->start_time,
                    'to'        => $e->end_time,
                    'cancelled' => $e->is_cancelled ? true : null,
                ]))->all();
        }

        if ($this->scope->can('credit')) {
            $pending = $this->pin(CreditQuery::query(), $orgId)
                ->whereIn('status', ['pending', 'processing'])->count();

            if ($pending > 0) {
                $out['credit_requests_awaiting_decision'] = $pending;
            }
        }

        return $this->clean($out);
    }

    /**
     * Student attendance for one day — and, when it is one school, which
     * classes were marked and which were left alone.
     *
     * "Not marked" and "everybody absent" are completely different facts, and
     * the panel treats them as such; so must this.
     */
    private function studentAttendanceOfDay(?int $orgId, string $date): array
    {
        $q = $this->pin(StudentAttendance::query(), $orgId)->whereDate('attendance_date', $date);

        $counts  = (clone $q)->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');
        $present = (int) ($counts[1] ?? 0);
        $absent  = (int) ($counts[0] ?? 0);
        $half    = (int) ($counts[2] ?? 0);
        $holiday = (int) ($counts[3] ?? 0);
        $counted = $present + $absent + $half;

        $out = [
            'marked'          => ($counted + $holiday) > 0,
            'students_marked' => $counted + $holiday,
            'present'         => $present,
            'absent'          => $absent,
            'half_day'        => $half,
            'holiday'         => $holiday,
            'present_pct'     => $counted > 0 ? round(($present + $half * 0.5) / $counted * 100, 1) : null,
        ];

        if (! $orgId) {
            return $this->clean($out);
        }

        $out['students_total'] = StudentDetail::where('organization_id', $orgId)->count();

        $classes = Standard::where('organization_id', $orgId)->orderBy('order')->orderBy('id')->pluck('name', 'id')->all();

        $markedIds = StudentDetail::where('organization_id', $orgId)
            ->whereIn('id', StudentAttendance::where('organization_id', $orgId)
                ->whereDate('attendance_date', $date)->select('student_detail_id'))
            ->distinct()->pluck('standard_id')->filter()->all();

        $marked = array_values(array_intersect_key($classes, array_flip($markedIds)));

        $out['classes_marked']     = $marked;
        $out['classes_not_marked'] = array_values(array_diff(array_values($classes), $marked));

        if (! $out['marked']) {
            $out['note'] = 'Student attendance was never marked for this date — that is not the same as everybody being absent.';
        }

        return $this->clean($out);
    }

    /**
     * Teacher and employee attendance for one day, with how many of the staff
     * were marked at all — so "is teacher attendance done today" has a real
     * answer instead of being inferred from the student numbers.
     */
    private function staffAttendanceOfDay(?int $orgId, string $date): array
    {
        $teachers = $this->pin(TeacherAttendance::query(), $orgId)->whereDate('attendance_date', $date);
        $tCounts  = $this->tallyAttendance((clone $teachers)->selectRaw('status, COUNT(*) c')
            ->groupBy('status')->pluck('c', 'status')->all());

        $out = ['teachers' => $tCounts + [
            'marked'         => array_sum($tCounts) > 0,
            'teachers_total' => $this->pin(TeacherDetail::query(), $orgId)->count(),
            'absent_names'   => (clone $teachers)->where('status', 0)
                ->with('teacherDetail.user:id,name')->limit(25)->get()
                ->map(fn ($r) => $r->teacherDetail->user->name ?? null)
                ->filter()->values()->all(),
        ]];

        $employees = $this->pin(AdminAttendance::query(), $orgId)->whereDate('date', $date);
        $eCounts   = $this->tallyAttendance((clone $employees)->selectRaw('status, COUNT(*) c')
            ->groupBy('status')->pluck('c', 'status')->all());

        $out['employees'] = $eCounts + [
            'marked'          => array_sum($eCounts) > 0,
            'employees_total' => $this->pin(AdminEmployee::query(), $orgId)->count(),
            'absent_names'    => (clone $employees)->whereIn('status', [0, 'absent'])
                ->with('employee:id,name')->limit(25)->get()
                ->map(fn ($r) => $r->employee->name ?? null)
                ->filter()->values()->all(),
        ];

        if (! $out['teachers']['marked']) {
            $out['teachers']['note'] = 'Teacher attendance was never marked for this date.';
        }
        if (! $out['employees']['marked']) {
            $out['employees']['note'] = 'Employee attendance was never marked for this date.';
        }

        return $out;
    }

    /**
     * Attendance rows come back keyed by whatever the table stores — teachers
     * use 0/1/2/3, non-teaching staff an enum of words, including 'leave'.
     * Anything unrecognised is counted as `other` rather than quietly folded
     * into "present", which is how somebody on leave used to be reported as
     * having turned up.
     *
     * @param  array<int|string,int>  $rows  status => count
     * @return array<string,int>
     */
    private function tallyAttendance(array $rows): array
    {
        $counts = ['present' => 0, 'absent' => 0, 'half_day' => 0, 'holiday' => 0, 'leave' => 0, 'other' => 0];

        foreach ($rows as $status => $n) {
            $label = match (strtolower((string) $status)) {
                '1', 'present'              => 'present',
                '0', 'absent'               => 'absent',
                '2', 'half_day', 'half day' => 'half_day',
                '3', 'holiday'              => 'holiday',
                'leave', '4'                => 'leave',
                default                     => 'other',
            };

            $counts[$label] += (int) $n;
        }

        return array_filter($counts, fn (int $n) => $n > 0) + ['present' => 0, 'absent' => 0];
    }

    // ══════════════════════════════════════════════════════════════════
    // Credit raised with SuperLMS
    // ══════════════════════════════════════════════════════════════════

    /**
     * The school's own credit requests, which it raises from its panel and
     * SuperLMS approves. They used to be readable only from the platform side,
     * so a school admin asking about the request they had just sent was told
     * there was no such data.
     */
    private function creditRequests(array $a): array
    {
        $orgId = $this->effectiveOrganizationId($a);
        $cross = $this->spansSchools($orgId);
        $limit = $this->limit($a, 20);

        $q = $this->pin(CreditQuery::query(), $orgId);

        $status = strtolower((string) ($a['status'] ?? 'any'));
        if (in_array($status, ['pending', 'processing', 'approved', 'denied'], true)) {
            $q->where('status', $status);
        }

        $rows = (clone $q)
            ->with(array_values(array_filter([$cross ? 'organization:id,name' : null, 'approvedBy:id,name'])))
            ->orderByDesc('id')->limit($limit)->get()
            ->map(function (CreditQuery $c) use ($cross) {
                $repay = $c->repayment();

                return $this->clean([
                    'school'        => $cross ? ($c->organization->name ?? null) : null,
                    'heading'       => $c->heading,
                    'reason'        => $c->reason,
                    'amount'        => round((float) $c->amount, 2),
                    'status'        => $c->status,
                    'from'          => optional($c->start_date)->toDateString(),
                    'due_on'        => optional($c->end_date)->toDateString(),
                    'penalty_per_day' => round((float) $c->penalties_per_day, 2),
                    'penalty_so_far'  => $repay['penalty'],
                    'payable_now'     => $repay['total'],
                    'days_overdue'    => $repay['days_overdue'] ?: null,
                    'days_left'       => $repay['days_left'] ?: null,
                    'settled'         => $repay['settled'],
                    'collected_on'    => optional($c->collected_at)->toDateString(),
                    'admin_remark'    => $c->admin_remark,
                    'approved_by'     => $c->approvedBy->name ?? null,
                    'requested_on'    => optional($c->created_at)->toDateString(),
                ]);
            })->all();

        $byStatus = (clone $q)->reorder()->selectRaw('status, COUNT(*) c, SUM(amount) total')
            ->groupBy('status')->get()
            ->mapWithKeys(fn ($r) => [($r->status ?: 'unset') => ['count' => (int) $r->c, 'total' => round((float) $r->total, 2)]])
            ->all();

        return $this->clean([
            'covers'    => $this->coverage($orgId),
            'total'     => (clone $q)->count(),
            'by_status' => $byStatus,
            'requests'  => $rows,
            'note'      => $rows === []
                ? 'No credit request has been raised' . ($status !== 'any' ? ' with that status' : '') . '.'
                : null,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // Platform documents (More → Privacy Policy, Terms, About)
    // ══════════════════════════════════════════════════════════════════

    /** The documents every panel links to, and where their text lives. */
    private const DOCUMENTS = [
        'privacy_policy'       => ['model' => PrivacyPolicy::class,   'title' => 'Privacy Policy'],
        'terms_of_use'         => ['model' => TermOfUse::class,       'title' => 'Terms of Use'],
        'terms_and_conditions' => ['model' => TermAndCondition::class, 'title' => 'Terms & Conditions'],
    ];

    /**
     * The Privacy Policy, the Terms and About App — readable, not raw JSON.
     *
     * Every panel has these on its More screen, so the assistant being unable
     * to say what its own privacy policy contains was a plain hole. They are
     * long (the policy alone runs to two dozen sections), so the default answer
     * is the headings plus an excerpt, and a search word or a section name
     * brings back full text.
     */
    private function policyDocument(array $a): array
    {
        $which = strtolower((string) ($a['document'] ?? 'list'));

        if ($which === 'about_app') {
            return $this->aboutApp();
        }

        if (! isset(self::DOCUMENTS[$which])) {
            return [
                'documents' => array_merge(
                    array_map(fn (array $d) => $d['title'], self::DOCUMENTS),
                    ['about_app' => 'About App — company name, CIN, contact details, team'],
                ),
                'how'       => 'Call policy_document again with one of these keys.',
            ];
        }

        $spec = self::DOCUMENTS[$which];
        $row  = $spec['model']::query()->orderBy('id')->first();

        if (! $row) {
            return ['error' => 'The ' . $spec['title'] . ' has not been published yet, so there is no text to read.'];
        }

        $sections = array_values(array_filter(
            (array) data_get($row->metadata, 'sections', []),
            fn ($s) => is_array($s) && (filled($s['head'] ?? null) || filled($s['desc'] ?? null)),
        ));

        // Stamp each one with its position in the document before anything is
        // filtered out, so a searched section is still cited by its real number.
        foreach ($sections as $i => $section) {
            $sections[$i]['number'] = $i + 1;
        }

        if ($sections === []) {
            return ['error' => 'The ' . $spec['title'] . ' has no sections recorded.'];
        }

        $search  = $this->text($a['search'] ?? null);
        $section = $this->text($a['section'] ?? null);

        // One named section, by number or by (part of) its heading.
        if ($section !== null) {
            $index = ctype_digit($section) ? ((int) $section) - 1 : null;

            $found = $index !== null
                ? array_slice($sections, max(0, $index), 1)
                : array_values(array_filter(
                    $sections,
                    fn ($s) => stripos((string) ($s['head'] ?? ''), $section) !== false,
                ));

            return $this->documentAnswer($spec['title'], $row, $found, count($sections), true);
        }

        if ($search !== null) {
            $found = array_values(array_filter($sections, fn ($s) => stripos(
                (string) ($s['head'] ?? '') . ' ' . (string) ($s['desc'] ?? ''),
                $search
            ) !== false));

            return $this->documentAnswer($spec['title'], $row, $found, count($sections), true) + [
                'searched_for' => $search,
            ];
        }

        return $this->documentAnswer($spec['title'], $row, $sections, count($sections), false);
    }

    /**
     * @param  array<int,array<string,mixed>>  $sections
     * @return array<string,mixed>
     */
    private function documentAnswer(string $title, Model $row, array $sections, int $total, bool $full): array
    {
        // Full text is capped per section and per answer: a legal document can
        // run to tens of thousands of characters, and a tool result that big
        // costs more than the question it answers.
        $sections = array_slice($sections, 0, $full ? 6 : 30);

        return $this->clean([
            'document'       => $title,
            'last_updated'   => optional($row->last_updated)->toDateString(),
            'total_sections' => $total,
            'showing'        => count($sections),
            'sections'       => array_map(fn (array $s, int $i) => $this->clean([
                'number' => (int) ($s['number'] ?? $i + 1),
                'head'   => trim((string) ($s['head'] ?? '')) ?: null,
                'text'   => \Illuminate\Support\Str::limit(
                    trim(strip_tags((string) ($s['desc'] ?? ''))),
                    $full ? 2000 : 220,
                ) ?: null,
            ]), $sections, array_keys($sections)),
            'note'           => $sections === []
                ? 'No section of the ' . $title . ' matches that.'
                : ($full ? null : 'These are excerpts. Ask again with `search` or `section` for a section in full.'),
        ]);
    }

    /** About App — who runs SuperLMS, and how to reach them. */
    private function aboutApp(): array
    {
        $about = AboutApp::query()->orderBy('id')->first();

        if (! $about) {
            return ['error' => 'About App has not been filled in yet.'];
        }

        return $this->clean([
            'document'     => 'About App',
            'heading'      => $about->heading,
            'sub_heading'  => $about->sub_heading,
            'company_name' => $about->company_name,
            'company_cin'  => $about->company_cin,
            'address'      => $about->address,
            'contact'      => $about->contact_details ?: null,
            'social_media' => $about->social_media ?: null,
            'team'         => $about->core_team ?: null,
            'content'      => $about->content ?: null,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // Argument handling — everything the model sends passes through here
    // ══════════════════════════════════════════════════════════════════

    /** Drop the nulls a row picks up when a field does not apply. */
    private function clean(array $row): array
    {
        return array_filter($row, fn ($v) => $v !== null);
    }

    /**
     * Read a whitelist of *columns* off a model. Deliberately not `only()`:
     * that goes through getAttribute(), which would happily load a relation
     * when a key happens to share a relation's name.
     *
     * @param  array<int,string>  $keys
     * @return array<string,mixed>
     */
    private function pick(Model $model, array $keys): array
    {
        $raw = array_intersect_key($model->getAttributes(), array_flip($keys));

        return array_filter($raw, fn ($v) => $v !== null && $v !== '');
    }

    private function text(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value !== '' ? mb_substr($value, 0, 120) : null;
    }

    private function limit(array $args, int $default): int
    {
        $n = (int) ($args['limit'] ?? $default);

        return max(1, min($n > 0 ? $n : $default, self::MAX_ROWS));
    }

    private function date(mixed $value): ?string
    {
        $value = $this->text($value);
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return array{0:?string,1:?string} */
    private function range(array $args): array
    {
        $from = $this->date($args['from'] ?? null);
        $to   = $this->date($args['to'] ?? null);

        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    private function standardId(mixed $name, ?int $orgId): ?int
    {
        $name = $this->text($name);
        if (! $name) {
            return null;
        }

        return $this->pin(Standard::query(), $orgId)
            ->where(fn ($w) => $w->where('name', $name)->orWhere('name', 'like', "%{$name}%"))
            ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', [$name])
            ->value('id');
    }

    private function sectionId(mixed $name, ?int $standardId, ?int $orgId): ?int
    {
        $name = $this->text($name);
        if (! $name) {
            return null;
        }

        $q = $this->pin(Section::query(), $orgId);
        if ($standardId) {
            $q->where('standard_id', $standardId);
        }

        return $q->where('name', 'like', "%{$name}%")->value('id');
    }

    /**
     * Resolve a school by name or serial, within what this caller may see. A
     * pinned reader can only ever resolve their own, so a `school` argument
     * they should not have is a dead end rather than a way out.
     */
    private function examId(mixed $name, ?int $orgId): ?int
    {
        $name = $this->text($name);
        if (! $name) {
            return null;
        }

        return $this->pin(Exam::query(), $orgId)
            ->where(fn ($w) => $w->where('exam_name', $name)->orWhere('exam_name', 'like', "%{$name}%"))
            ->orderByRaw('CASE WHEN exam_name = ? THEN 0 ELSE 1 END', [$name])
            ->orderByDesc('id')
            ->value('id');
    }

    private function subjectId(mixed $name, ?int $orgId): ?int
    {
        $name = $this->text($name);
        if (! $name) {
            return null;
        }

        return $this->pin(Subject::query(), $orgId)
            ->where(fn ($w) => $w->where('name', $name)->orWhere('name', 'like', "%{$name}%"))
            ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', [$name])
            ->value('id');
    }

    /** One student, by admission number or name. */
    private function studentRefId(mixed $ref, ?int $orgId): ?int
    {
        $ref = $this->text($ref);
        if (! $ref) {
            return null;
        }

        return $this->pin(StudentDetail::query(), $orgId)
            ->where(fn ($w) => $w
                ->where('admission_no', $ref)
                ->orWhere('full_name', $ref)
                ->orWhere('full_name', 'like', "%{$ref}%"))
            ->orderByRaw('CASE WHEN admission_no = ? OR full_name = ? THEN 0 ELSE 1 END', [$ref, $ref])
            ->value('id');
    }

    private function findSchool(mixed $needle): ?Organization
    {
        $needle = $this->text($needle);
        if (! $needle) {
            return null;
        }

        return $this->schoolsQuery()
            ->where(fn ($w) => $w
                ->where('name', $needle)
                ->orWhere('serial_number', $needle)
                ->orWhere('name', 'like', "%{$needle}%"))
            ->first();
    }

    // ── declaration helpers ──────────────────────────────────────────────

    /** @param array<string,array<string,mixed>> $properties */
    private function schema(array $properties, array $required = []): array
    {
        $schema = ['type' => 'OBJECT', 'properties' => $properties];
        if ($required !== []) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    private function str(string $description): array
    {
        return ['type' => 'STRING', 'description' => $description];
    }

    private function int(string $description): array
    {
        return ['type' => 'INTEGER', 'description' => $description];
    }

    private function bool(string $description): array
    {
        return ['type' => 'BOOLEAN', 'description' => $description];
    }

    /** @param  array<string,mixed>  $items */
    private function arr(string $description, array $items): array
    {
        return ['type' => 'ARRAY', 'description' => $description, 'items' => $items];
    }

    private function enum(array $values, string $description): array
    {
        return ['type' => 'STRING', 'enum' => $values, 'description' => $description];
    }
}
