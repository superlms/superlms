<?php

namespace App\Services\Gemini;

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
use App\Models\Admin\Transportation;
use App\Models\Admin\TransferCertificate;
use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Models\SuperAdmin\CreditQuery;
use App\Models\SuperAdmin\SuperAdminFeePayment;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
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
 */
class LmsToolbox
{
    private const MAX_ROWS = 40;

    /** How day_of_week is stored on the timetable (1 = Monday). */
    private const WEEKDAYS = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    /** Record types only the platform panel may list. */
    private const PLATFORM_ENTITIES = ['credit_queries', 'support_messages', 'ratings', 'demo_requests', 'schools'];

    private const SCHOOL_ENTITIES = [
        'announcements', 'homework', 'exams', 'certificates', 'transfer_certificates',
        'admission_enquiries', 'ledger', 'transport', 'books', 'fee_structures',
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

        return $this->scope->isPlatform()
            ? array_merge($tools, $this->platformToolDeclarations())
            : $tools;
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

        $entities = $this->scope->isPlatform()
            ? array_merge(self::SCHOOL_ENTITIES, self::PLATFORM_ENTITIES)
            : self::SCHOOL_ENTITIES;

        return [
            [
                'name'        => 'search_students',
                'description' => 'Find students by name, admission number, roll number, father/mother name, class or section, and count how many match.' . $note,
                'parameters'  => $this->schema($school + [
                    'query'    => $this->str('Free text: part of a name, admission no or roll no.'),
                    'standard' => $this->str('Class name, e.g. "10th".'),
                    'section'  => $this->str('Section name, e.g. "A".'),
                    'limit'    => $this->int('Max rows to return (default 20, max 40).'),
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
                    'limit' => $this->int('Max rows (default 20, max 40).'),
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
                    'limit'    => $this->int('Max payment rows listed (default 20, max 40).'),
                ]),
            ],
            [
                'name'        => 'fee_defaulters',
                'description' => 'Students whose paid amount is below what their class is charged by the active fee structures. Use for "who has pending fees", "defaulters of 10th". Needs one school.',
                'parameters'  => $this->schema($school + [
                    'standard' => $this->str('Limit to one class.'),
                    'limit'    => $this->int('Max rows (default 20, max 40).'),
                ]),
            ],
            [
                'name'        => 'attendance_report',
                'description' => 'Student attendance counts (present / absent / half day / holiday) for a date or a date range, optionally for one class.' . $note,
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
                    'limit'    => $this->int('How many students to list, best first (default 20, max 40) — pass 3 for "top 3".'),
                ]),
            ],
            [
                'name'        => 'exam_schedule',
                'description' => 'The datesheet: which subject is examined on which date and at what time, for a class. Use for "when is the maths paper", "exam schedule of 10th".' . $note,
                'parameters'  => $this->schema($school + [
                    'exam'     => $this->str('Exam name. Leave out for the most recent datesheet.'),
                    'standard' => $this->str('Class name.'),
                    'section'  => $this->str('Section name.'),
                    'limit'    => $this->int('Max papers to list (default 20, max 40).'),
                ]),
            ],
            [
                'name'        => 'staff_attendance',
                'description' => 'Teacher and employee attendance (present / absent / half day / holiday) for a date or a range, with the names marked absent.' . $note,
                'parameters'  => $this->schema($school + [
                    'date'  => $this->str('A single day, YYYY-MM-DD. Defaults to today when no range is given.'),
                    'from'  => $this->str('Range start, YYYY-MM-DD.'),
                    'to'    => $this->str('Range end, YYYY-MM-DD.'),
                    'type'  => $this->enum(['teacher', 'employee', 'any'], 'Which staff list (default any).'),
                    'limit' => $this->int('Max names listed (default 20, max 40).'),
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
                    'limit'    => $this->int('Max rows (default 20, max 40).'),
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
                    'limit' => $this->int('Max entries listed (default 20, max 40).'),
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
                    'limit'    => $this->int('Max periods (default 40, max 40).'),
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
                    'where'      => $this->arr('Filters, all of which must hold.', $this->schema([
                        'field' => $this->str('Field name, exactly as describe_data spells it.'),
                        'op'    => $this->enum(['eq', 'ne', 'gt', 'gte', 'lt', 'lte', 'like', 'in', 'is_null', 'not_null', 'empty', 'not_empty'], 'Comparison. Use not_empty for "has a value" (a photo, a phone number) and empty for "is missing".'),
                        'value' => $this->str('The value to compare against. Leave out for is_null / not_null / empty / not_empty. For "in", separate values with commas.'),
                    ], ['field', 'op'])),
                    'fields'     => $this->arr('Which fields to return. Leave out for a sensible default.', $this->str('Field name.')),
                    'order_by'   => $this->str('Field to sort by (default the newest first).'),
                    'direction'  => $this->enum(['asc', 'desc'], 'Sort direction (default desc).'),
                    'count_only' => $this->bool('True to return only how many rows match, without listing them.'),
                    'limit'      => $this->int('Max rows (default 20, max 40).'),
                ], ['entity']),
            ],
            [
                'name'        => 'aggregate_records',
                'description' => 'Count, total or average any field of any record type, optionally grouped. Use for "how many students per class", "total expense by reason", "average marks by section".' . $note,
                'parameters'  => $this->schema($school + [
                    'entity'   => $this->str('Record type from describe_data.'),
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
                    'limit'  => $this->int('Max rows (default 10, max 40).'),
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
                    'limit'  => $this->int('Max rows (default 20, max 40).'),
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
                    'limit'  => $this->int('Max rows (default 20, max 40).'),
                ]),
            ],
            [
                'name'        => 'search_users',
                'description' => 'Find login accounts across the platform — admins, sub-admins, accounts users, teachers, students and super-admins — by name, email, mobile, role or school. Use for "who are the admins of X", "how many teacher logins exist", "find this email". Returns a count per role as well as the matching accounts.',
                'parameters'  => $this->schema([
                    'query'  => $this->str('Free text: name, email or mobile.'),
                    'role'   => $this->enum(['admin', 'sub-admin', 'accounts', 'teacher', 'user', 'super-admin', 'sub-super-admin', 'any'], 'Limit to one role (default any). "user" is a student login.'),
                    'school' => $this->str('Limit to one school, by name or serial number.'),
                    'active' => $this->enum(['yes', 'no', 'any'], 'Only enabled logins, only disabled ones, or both (default any).'),
                    'limit'  => $this->int('Max rows (default 20, max 40).'),
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

        return [
            'covers'   => $this->coverage($orgId),
            'matched'  => (clone $q)->count(),
            'showing'  => count($rows),
            'students' => $rows,
        ];
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

        $matches = $q->limit(6)->get();

        if ($matches->isEmpty()) {
            return ['found' => false, 'message' => 'No student matched in ' . $this->coverage($orgId) . '.'];
        }
        if ($matches->count() > 1) {
            return [
                'found'      => false,
                'message'    => 'More than one student matched — ask the user which one.',
                'candidates' => $matches->map(fn ($s) => $this->clean([
                    'school'       => $cross ? ($s->organization->name ?? null) : null,
                    'name'         => $s->full_name,
                    'admission_no' => $s->admission_no,
                    'class'        => $s->standard->name ?? null,
                ]))->all(),
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
            ->select(['id', 'name', 'email', 'mobile_number', 'role', 'is_active', 'organization_id', 'last_login_at', 'created_at']);

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

        return [
            'covers'       => $this->coverage($orgId),
            'period'       => ['from' => $from, 'to' => $to],
            'class'        => $std ? Standard::find($std)?->name : 'all classes',
            'present'      => $present,
            'absent'       => $absent,
            'half_day'     => $half,
            'holiday'      => $holiday,
            'marked_total' => $marked,
            'present_pct'  => $marked > 0 ? round(($present + $half * 0.5) / $marked * 100, 1) : null,
            'note'         => $marked === 0 ? 'Attendance has not been marked for this period.' : null,
        ];
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

        $tally = function ($rows) {
            $counts = ['present' => 0, 'absent' => 0, 'half_day' => 0, 'holiday' => 0];
            foreach ($rows as $status => $n) {
                $label = match ((string) $status) {
                    '1', 'present' => 'present',
                    '0', 'absent'  => 'absent',
                    '2', 'half_day', 'half day' => 'half_day',
                    '3', 'holiday' => 'holiday',
                    default        => 'present',
                };
                $counts[$label] += (int) $n;
            }

            return $counts;
        };

        if ($type !== 'employee') {
            $q = $this->pin(TeacherAttendance::query(), $orgId);
            if ($from) {
                $q->whereDate('attendance_date', '>=', $from);
            }
            if ($to) {
                $q->whereDate('attendance_date', '<=', $to);
            }

            $out['teachers'] = $tally(
                (clone $q)->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status')->all()
            );
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

            $out['employees'] = $tally(
                (clone $q)->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status')->all()
            );
            $out['employees_absent'] = (clone $q)->whereIn('status', [0, 'absent'])
                ->with('employee:id,name')
                ->limit($limit)->get()
                ->map(fn ($r) => $this->clean([
                    'name' => $r->employee->name ?? null,
                    'date' => $r->date ? Carbon::parse($r->date)->toDateString() : null,
                ]))->values()->all();
        }

        $marked = array_sum($out['teachers'] ?? []) + array_sum($out['employees'] ?? []);
        if ($marked === 0) {
            $out['note'] = 'Staff attendance has not been marked for this period.';
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
            $q->where(fn ($w) => $w
                ->where('party', 'like', "%{$party}%")
                ->orWhere('party_to', 'like', "%{$party}%")
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
                'party'  => $t->party ?: $t->party_to,
                'mode'   => $t->mode,
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

        return [
            'covers'       => $this->coverage($orgId),
            'entity'       => $key,
            'about'        => $entity['label'],
            'fields'       => $columns,
            'searchable'   => array_values(array_intersect($entity['search'], $columns)),
            'rows_visible' => $query->count(),
        ];
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

        [$applied, $rejected] = $this->applyWhere($query, (array) ($a['where'] ?? []), $columns);
        $this->applySearch($query, $a['search'] ?? null, $entity, $columns);

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

        $fields = array_values(array_intersect(
            array_map(fn ($f) => (string) $f, (array) ($a['fields'] ?? [])),
            $columns,
        ));

        if ($fields === []) {
            $fields = $this->defaultFields($columns, $entity);
        }

        // Ids the row needs for its labels have to be selected even when the
        // caller did not ask for them.
        $select = array_values(array_unique(array_merge($fields, array_values(array_intersect(array_keys(self::LABEL_ALIAS), $columns)))));

        $order     = in_array($this->text($a['order_by'] ?? null), $columns, true) ? $this->text($a['order_by']) : (in_array('id', $columns, true) ? 'id' : $columns[0]);
        $direction = strtolower((string) ($a['direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $rows = $query->orderBy($order, $direction)
            ->limit($this->limit($a, 20))
            ->get($select)
            ->map(fn (Model $row) => $row->getAttributes())
            ->all();

        return $head + [
            'fields' => $fields,
            'rows'   => $this->labelRows($rows, $fields),
            'note'   => $rows === [] ? 'Nothing matches this filter.' : null,
        ];
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
        $field  = in_array($this->text($a['field'] ?? null), $columns, true) ? $this->text($a['field']) : null;
        $group  = in_array($this->text($a['group_by'] ?? null), $columns, true) ? $this->text($a['group_by']) : null;

        if ($metric !== 'count' && ! $field) {
            return ['error' => 'That calculation needs a numeric field that exists on this record type.'];
        }

        [$applied, $rejected] = $this->applyWhere($query, (array) ($a['where'] ?? []), $columns);
        $this->applySearch($query, $a['search'] ?? null, $entity, $columns);

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

        // Pinned through a parent that does carry the school.
        foreach (['student_detail_id' => StudentDetail::class, 'standard_id' => Standard::class, 'section_id' => Section::class] as $column => $parent) {
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
    private function applyWhere(Builder $query, array $clauses, array $columns): array
    {
        $applied = $rejected = [];

        foreach (array_slice($clauses, 0, 8) as $clause) {
            $clause = (array) $clause;
            $field  = $this->text($clause['field'] ?? null);
            $op     = strtolower((string) ($clause['op'] ?? 'eq'));
            $value  = $clause['value'] ?? null;
            $value  = is_scalar($value) ? (string) $value : null;

            if (! $field || ! in_array($field, $columns, true)) {
                $rejected[] = 'no such field: ' . ($field ?: '(blank)');
                continue;
            }

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
                // "has a photo" is not the same as "the column is not null":
                // an empty string is how this app records "nothing uploaded".
                'empty'     => $query->where(fn ($w) => $w->whereNull($field)->orWhere($field, '')),
                'not_empty' => $query->whereNotNull($field)->where($field, '!=', ''),
                default     => $rejected[] = 'unknown comparison: ' . $op,
            };

            if (! str_starts_with(end($rejected) ?: '', 'unknown comparison')) {
                $applied[] = trim($field . ' ' . $op . ' ' . (string) $value);
            }
        }

        return [$applied, $rejected];
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
                ->map(fn ($r) => ['school' => $r->organization->name ?? null, 'heading' => $r->heading, 'amount' => (float) $r->amount, 'status' => $r->status, 'from' => (string) $r->start_date, 'to' => (string) $r->end_date])->all()],

            'support_messages' => ['support_messages' => $this->pin(ContactSuperAdmin::query(), $orgId)->with('organization:id,name')
                ->orderByDesc('id')->limit($limit)->get()
                ->map(fn ($r) => array_merge(['school' => $r->organization->name ?? null], $this->pick($r, ['subject', 'title', 'message', 'description', 'status', 'created_at'])))->all()],

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
