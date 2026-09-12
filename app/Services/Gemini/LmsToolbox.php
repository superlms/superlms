<?php

namespace App\Services\Gemini;

use App\Models\Admin\AdmissionEnquiry;
use App\Models\Admin\AdminEmployee;
use App\Models\Admin\Announcement;
use App\Models\Admin\Book;
use App\Models\Admin\Certificate;
use App\Models\Admin\ContactSuperAdmin;
use App\Models\Admin\Exam;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\HomeWork;
use App\Models\Admin\LedgerTransaction;
use App\Models\Admin\RateLms;
use App\Models\Admin\Transportation;
use App\Models\Admin\TransferCertificate;
use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\SuperAdmin\CreditQuery;
use App\Models\SuperAdmin\SuperAdminFeePayment;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use App\Models\WebsiteDemo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * The read-only query surface Gemini is allowed to call.
 *
 * Every tool is a hand-written Eloquent query — the model never supplies SQL, a
 * table name or an organization id. Arguments are whitelisted and clamped here.
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
            return match ($name) {
                'search_students'       => $this->searchStudents($args),
                'student_profile'       => $this->studentProfile($args),
                'class_roster'          => $this->classRoster($args),
                'search_staff'          => $this->searchStaff($args),
                'search_users'          => $this->searchUsers($args),
                'fee_payments'          => $this->feePayments($args),
                'fee_defaulters'        => $this->feeDefaulters($args),
                'attendance_report'     => $this->attendanceReport($args),
                'recent_records'        => $this->recentRecords($args),
                'search_schools'        => $this->searchSchools($args),
                'school_overview'       => $this->schoolOverview($args),
                'platform_fee_payments' => $this->platformFeePayments($args),
                default                 => ['error' => 'Tool not implemented.'],
            };
        } catch (\Throwable $e) {
            Log::warning('gemini.tool failed', ['tool' => $name, 'error' => $e->getMessage()]);

            return ['error' => 'That data could not be read right now.'];
        }
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

    private function enum(array $values, string $description): array
    {
        return ['type' => 'STRING', 'enum' => $values, 'description' => $description];
    }
}
