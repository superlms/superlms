<?php

namespace App\Models;

use App\Models\Admin\SchoolInfo;
use App\Models\Student\Section;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Models\Teacher\TeacherDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Organization extends Model
{
    /** Per-request cache of whether the module_organization table exists. */
    private static ?bool $moduleTableExists = null;

    /** Per-instance cache of this org's module_key => enabled map. */
    protected ?array $moduleMapCache = null;

    protected $fillable = [
        'name',
        'email',
        'mobile_number',
        'state',
        'education_board',
        'medium',
        'school_code',
        'affiliation_no',
        'udise_number',
        'serial_number',
        'status',
        'logo',
        'address',
        'bank_name',
        'bank_account_no',
        'bank_ifsc',
        'bank_branch',
        'bank_holder_name',
    ];

    protected $casts = ['status' => 'boolean'];

    protected $table = 'organizations';

    /**
     * Deleting a school permanently wipes ALL of its data — students,
     * teachers, employees, users and every org-scoped record across the
     * schema — no matter which code path triggered the delete.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $organization) {
            $organization->purgeSchoolData();
        });

        // A teacher's username ends in the school code, so a new code carries
        // over to them: meera@006 becomes meera@asic.
        static::updated(function (self $organization) {
            if ($organization->wasChanged('school_code')) {
                \App\Support\Usernames::followSchoolCode($organization->id);
            }
        });
    }

    /**
     * Hard-delete every scrap of data belonging to this school.
     *
     * Two passes:
     *   1. Every table that carries an `organization_id` column (discovered at
     *      runtime, so it survives schema drift and future tables) is wiped for
     *      this org — this covers the ~75 org-scoped tables incl. users.
     *   2. Child tables that are scoped only through a parent relation (and so
     *      carry no organization_id of their own) are wiped by their parent IDs.
     *
     * FK checks are disabled for the duration so delete order doesn't matter;
     * every statement stays tightly scoped to this organization.
     */
    public function purgeSchoolData(): void
    {
        $orgId = (int) $this->id;
        if ($orgId <= 0) {
            return;
        }

        $isMysql = DB::getDriverName() === 'mysql';

        // Parent keys needed for child tables that lack organization_id.
        $userIds       = $this->orgScopedIds('users',              $orgId);
        $standardIds   = $this->orgScopedIds('standards',          $orgId);
        $schoolInfoIds = $this->orgScopedIds('school_infos',       $orgId);
        $convoIds      = $this->orgScopedIds('chat_conversations', $orgId);
        $roomIds       = $this->orgScopedIds('seating_rooms',      $orgId);
        $planIds       = $this->orgScopedIds('seating_plans',      $orgId);

        // Pass 3's parents: this school's rows that other tables point at by
        // their own key, taken before anything is deleted.
        $studentIds   = $this->orgScopedIds('student_details', $orgId)
            ->merge($this->idsWhereIn('student_details', 'user_id', $userIds))
            ->unique()->values();
        $teacherIds   = $this->orgScopedIds('teacher_details', $orgId);
        $homeWorkIds  = $this->orgScopedIds('home_works',      $orgId);
        $announceIds  = $this->orgScopedIds('announcements',   $orgId);
        $datesheetIds = $this->orgScopedIds('exam_datesheets', $orgId);
        $messageIds   = $this->idsWhereIn('chat_messages', 'conversation_id', $convoIds);

        if ($isMysql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        try {
            // ── Pass 1: everything with an organization_id column ──────────────
            foreach ($this->tablesWithOrganizationId() as $table) {
                DB::table($table)->where('organization_id', $orgId)->delete();
            }

            // ── Pass 2: relation-only children (no organization_id) ────────────
            $this->deleteChildren('sections',               'standard_id',     $standardIds);
            // student_details carries organization_id via lms:migrate, but delete
            // by relation too as a safety net so students are always removed.
            $this->deleteChildren('student_details',        'standard_id',     $standardIds);
            $this->deleteChildren('student_details',        'user_id',         $userIds);
            $this->deleteChildren('seating_seats',          'room_id',         $roomIds);
            $this->deleteChildren('seat_assignments',       'seating_plan_id', $planIds);
            $this->deleteChildren('invigilator_assignments','seating_plan_id', $planIds);
            $this->deleteChildren('chat_conversation_user', 'conversation_id', $convoIds);
            $this->deleteChildren('chat_messages',          'conversation_id', $convoIds);
            $this->deleteChildren('user_fcm_tokens',        'user_id',         $userIds);
            $this->deleteChildren('notifications',          'notifiable_id',   $userIds);
            $this->deleteChildren('personal_access_tokens', 'tokenable_id',    $userIds);
            $this->deleteChildren('school_documents',       'school_info_id',  $schoolInfoIds);
            $this->deleteChildren('school_management_teams','school_info_id',  $schoolInfoIds);

            // ── Pass 3: every other table without organization_id that points
            //    at this school's people or records by one of these keys
            //    (announcement reads, homework completions, chat blocks and
            //    deleted-message marks, datesheet papers, role_user, sessions,
            //    account set-up links, …) — found at runtime, as pass 1 is ──
            $byKey = [
                'user_id'           => $userIds,
                'sender_id'         => $userIds,
                'blocked_user_id'   => $userIds,
                'student_detail_id' => $studentIds,
                'teacher_detail_id' => $teacherIds,
                'home_work_id'      => $homeWorkIds,
                'announcement_id'   => $announceIds,
                'exam_datesheet_id' => $datesheetIds,
                'message_id'        => $messageIds,
            ];
            foreach ($this->unscopedTablesWith(array_keys($byKey)) as [$table, $column]) {
                $this->deleteInChunks($table, $column, $byKey[$column]);
            }
        } finally {
            if ($isMysql) {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }
    }

    /** Ids of rows in $table belonging to this org (empty if table/column absent). */
    private function orgScopedIds(string $table, int $orgId)
    {
        return (Schema::hasTable($table) && Schema::hasColumn($table, 'organization_id'))
            ? DB::table($table)->where('organization_id', $orgId)->pluck('id')
            : collect();
    }

    /** Delete child rows whose $column matches any of $ids (guarded for drift). */
    private function deleteChildren(string $table, string $column, $ids): void
    {
        if ($ids->isEmpty() || !Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)->whereIn($column, $ids->all())->delete();
    }

    /** Ids of rows in $table whose $column matches any of $ids (empty if table/column absent). */
    private function idsWhereIn(string $table, string $column, $ids)
    {
        if ($ids->isEmpty() || !Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return collect();
        }

        return $ids->chunk(1000)
            ->flatMap(fn ($chunk) => DB::table($table)->whereIn($column, $chunk->values()->all())->pluck('id'))
            ->values();
    }

    /** Delete rows whose $column matches any of $ids, a thousand ids at a time. */
    private function deleteInChunks(string $table, string $column, $ids): void
    {
        foreach ($ids->chunk(1000) as $chunk) {
            DB::table($table)->whereIn($column, $chunk->values()->all())->delete();
        }
    }

    /**
     * [table, column] for every table WITHOUT an organization_id column that
     * has one of $columns — the school's data found only through a parent.
     * The Super Admin's own tables are left out.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function unscopedTablesWith(array $columns): array
    {
        $scoped = $this->tablesWithOrganizationId();
        $keep   = fn ($t) => $t !== 'organizations' && !in_array($t, $scoped, true) && !str_starts_with($t, 'super_admin');

        if (DB::getDriverName() === 'mysql') {
            $marks = implode(',', array_fill(0, count($columns), '?'));
            $rows  = DB::select(
                "SELECT table_name AS t, column_name AS c FROM information_schema.columns
                 WHERE table_schema = ? AND column_name IN ($marks)",
                array_merge([DB::getDatabaseName()], $columns)
            );

            return collect($rows)
                ->filter(fn ($r) => $keep($r->t))
                ->map(fn ($r) => [$r->t, $r->c])
                ->values()
                ->all();
        }

        // Non-MySQL (e.g. sqlite in tests): inspect each table's columns.
        $out = [];
        foreach (Schema::getTableListing() as $t) {
            $t = str_contains($t, '.') ? explode('.', $t)[1] : $t;
            if (!$keep($t)) {
                continue;
            }
            foreach ($columns as $c) {
                if (Schema::hasColumn($t, $c)) {
                    $out[] = [$t, $c];
                }
            }
        }

        return $out;
    }

    /** All table names carrying an organization_id column, except organizations itself. */
    private function tablesWithOrganizationId(): array
    {
        if (DB::getDriverName() === 'mysql') {
            $rows = DB::select(
                'SELECT table_name AS t FROM information_schema.columns
                 WHERE table_schema = ? AND column_name = ?',
                [DB::getDatabaseName(), 'organization_id']
            );

            return collect($rows)
                ->pluck('t')
                ->reject(fn ($t) => $t === 'organizations')
                ->values()
                ->all();
        }

        // Non-MySQL (e.g. sqlite in tests): inspect each table's columns.
        return collect(Schema::getTableListing())
            ->map(fn ($t) => str_contains($t, '.') ? explode('.', $t)[1] : $t)
            ->reject(fn ($t) => $t === 'organizations')
            ->filter(fn ($t) => Schema::hasColumn($t, 'organization_id'))
            ->values()
            ->all();
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
    public function schoolInfo()
    {
        return $this->hasOne(SchoolInfo::class, 'organization_id');
    }
    public function students()
    {
        return $this->hasMany(StudentDetail::class, 'organization_id');
    }
    public function teachers()
    {
        return $this->hasMany(TeacherDetail::class, 'organization_id');
    }
    public function feeStructures()
    {
        return $this->hasMany(\App\Models\SuperAdmin\SuperAdminFeeStructure::class, 'organization_id');
    }
    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    // ─── Per-school module (feature) access ────────────────────────────────

    public function modules()
    {
        return $this->hasMany(OrganizationModule::class, 'organization_id');
    }

    /**
     * Is a given module enabled for this school?
     *
     * Defaults to TRUE so existing schools (with no saved config) keep every
     * feature. Unknown / core keys are always enabled. Fails OPEN if the
     * config table is missing or unreadable — never hides features on error.
     */
    public function hasModule(string $key): bool
    {
        $modules = config('modules', []);

        // Core feature (not toggleable) → always available.
        if (!array_key_exists($key, $modules)) {
            return true;
        }

        $map = $this->moduleMap();

        if (array_key_exists($key, $map)) {
            return $map[$key];
        }

        // No explicit row saved yet → use the module's default (ON).
        return (bool) ($modules[$key]['default'] ?? true);
    }

    /** Load (and cache) this school's saved module_key => enabled(bool) map. */
    protected function moduleMap(): array
    {
        if ($this->moduleMapCache !== null) {
            return $this->moduleMapCache;
        }

        if (self::$moduleTableExists === null) {
            try {
                self::$moduleTableExists = Schema::hasTable('module_organization');
            } catch (\Throwable $e) {
                self::$moduleTableExists = false;
            }
        }

        if (!self::$moduleTableExists || !$this->id) {
            return $this->moduleMapCache = [];
        }

        try {
            return $this->moduleMapCache = OrganizationModule::query()
                ->where('organization_id', $this->id)
                ->pluck('enabled', 'module_key')
                ->map(fn($v) => (bool) $v)
                ->toArray();
        } catch (\Throwable $e) {
            return $this->moduleMapCache = [];
        }
    }
}
