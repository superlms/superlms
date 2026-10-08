<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Whatever is deleted takes its own records with it, so nothing of it is left
 * in the database (the user's ask of 8 Oct 2026: "jo bhi cheez delete ki jaye
 * vo permanently database se delete ho jaye" — fees and payments too).
 *
 * Run from the models' `deleting` events, so every place that deletes one of
 * them (web panel, apps, Super Admin) does it the same way:
 *
 *   student  — every row of the student's (student_detail_id: fees, transport
 *              fees, attendance, exam copies and marks, report and admit
 *              cards, certificates, TCs, ID cards, concessions, homework done,
 *              submissions, seats …) and of the student's login (user_id:
 *              chats, tokens, notifications, enquiries …)
 *   teacher  — the teacher's own rows (attendance, subjects, sections, class
 *              teacher rows, periods and arrangements, ID cards, payroll and
 *              its salary, enquiries …) and the login's; the marks and the
 *              events they entered for others are left
 *   exam     — its marks, admit cards, datesheets, seating plans, syllabus
 *   class, section, subject — their set-up and content (subjects, fee
 *              structures, books, syllabus, homework, teacher assignments,
 *              periods …), never a student's own fees or results
 *   any other parent (an event, homework, an assignment, an announcement …)
 *            — the rows that point at it by its key (time_table_id …)
 *
 * And so on down: a row that goes takes the rows pointing at it by the
 * conventional key (fee_payments → fee_payment_id …). The people, the classes,
 * sections, subjects and exams are never deleted on the side, nor the Super
 * Admin's own tables. A table that cannot be cleared is logged and skipped —
 * the delete asked for always goes through.
 */
class Cascade
{
    /** Never deleted on the side: the people, the school's structure, its exams. */
    private const KEEP = [
        'organizations', 'users', 'student_details', 'teacher_details', 'driver_details',
        'standards', 'sections', 'subjects', 'exams', 'roles', 'migrations',
    ];

    /** Tables whose user_id is who made the row (for others), not whose it is. */
    private const MADE_BY = [
        'announcements', 'chapters', 'topics', 'home_works', 'assignments', 'exam_copies', 'exam_papers',
        'admission_exam_papers', 'student_syllabi', 'libraries', 'rules_and_regulations', 'contact_super_admins',
        'mcq_questions', 'books', 'time_tables', 'blogs',
    ];

    /** The people pointed at under other names: [table, column]. */
    private const USER_ALIASES = [
        ['chat_messages', 'sender_id'], ['chat_blocks', 'blocked_user_id'],
        ['notifications', 'notifiable_id'], ['personal_access_tokens', 'tokenable_id'],
    ];
    private const STUDENT_ALIASES = [['seat_assignments', 'student_id']];
    private const TEACHER_ALIASES = [['teacher_arrangements', 'original_teacher_id'], ['teacher_arrangements', 'substitute_teacher_id']];

    /** A teacher's name on others' records: the marks they entered, the events they are named on. */
    private const TEACHER_LEAVES = ['exam_copies', 'time_table_academics'];

    /** Students' results without a student_detail_id of their own — a class, section or subject going leaves them. */
    private const STUDENTS_RESULTS = ['exam_subject_marks', 'admission_enquiries'];

    private const MAX_DEPTH = 5;

    /** column => tables having it (columns ending _id, and the aliases), for one connection. */
    private static ?array $index = null;
    private static ?int $indexFor = null;

    // ── What each kind takes with it ────────────────────────────────────────

    public static function student(int $studentDetailId, ?int $userId): void
    {
        self::deleteRefs(array_merge(self::refsTo('student_detail_id'), self::STUDENT_ALIASES), [$studentDetailId]);
        if ($userId) {
            self::user($userId);
        }
    }

    public static function teacher(int $teacherDetailId, ?int $userId): void
    {
        $refs = array_filter(self::refsTo('teacher_detail_id'), fn ($r) => !in_array($r[0], self::TEACHER_LEAVES, true));
        self::deleteRefs(array_merge($refs, self::TEACHER_ALIASES), [$teacherDetailId]);
        if ($userId) {
            self::user($userId);
        }
    }

    /** A student's or teacher's login: the rows that are theirs (not those they made for others). */
    public static function user(int $userId): void
    {
        $refs = array_filter(self::refsTo('user_id'), fn ($r) => !in_array($r[0], self::MADE_BY, true));
        self::deleteRefs(array_merge($refs, self::USER_ALIASES), [$userId]);
    }

    /** A class, section or subject: its set-up and content, never the students' own records. */
    public static function structure(string $table, int $id): void
    {
        $refs = array_filter(self::refsTo(Str::singular($table) . '_id'), fn ($r) => !in_array($r[0], self::STUDENTS_RESULTS, true)
            && !self::hasColumn($r[0], 'student_detail_id'));
        self::deleteRefs($refs, [$id]);
    }

    /** Anything else (an exam, an event, homework …): every row pointing at it by its key. */
    public static function children(string $table, int $id): void
    {
        self::childrenOf($table, [$id], 0);
    }

    // ── The work ────────────────────────────────────────────────────────────

    /** Delete the rows of each [table, column] whose column is one of $ids. */
    private static function deleteRefs(array $refs, array $ids, int $depth = 0): void
    {
        foreach ($refs as [$table, $column]) {
            if (self::kept($table) || !self::hasColumn($table, $column)) {
                continue;
            }
            self::deleteWhere($table, $column, $ids, $depth);
        }
    }

    /** The rows going first take what points at them; then they go. */
    private static function deleteWhere(string $table, string $column, array $ids, int $depth): void
    {
        try {
            foreach (array_chunk($ids, 1000) as $chunk) {
                if ($depth < self::MAX_DEPTH && self::hasColumn($table, 'id')) {
                    $childIds = DB::table($table)->whereIn($column, $chunk)->pluck('id')->all();
                    if ($childIds) {
                        self::childrenOf($table, $childIds, $depth + 1);
                    }
                }
                DB::table($table)->whereIn($column, $chunk)->delete();
            }
        } catch (\Throwable $e) {
            Log::warning('Cascade: could not clear a table', ['table' => $table, 'column' => $column, 'error' => $e->getMessage()]);
        }
    }

    /** Rows of $table are going: the rows pointing at them by its key go too. */
    private static function childrenOf(string $table, array $ids, int $depth): void
    {
        $refs = array_filter(self::refsTo(Str::singular($table) . '_id'), fn ($r) => $r[0] !== $table);
        foreach ($refs as [$child, $column]) {
            if (!self::kept($child)) {
                self::deleteWhere($child, $column, $ids, $depth);
            }
        }
    }

    private static function kept(string $table): bool
    {
        return in_array($table, self::KEEP, true) || str_starts_with($table, 'super_admin');
    }

    // ── The schema, read once a request ─────────────────────────────────────

    /** [table, column] for every table with $column. */
    private static function refsTo(string $column): array
    {
        return array_map(fn ($t) => [$t, $column], self::index()[$column] ?? []);
    }

    private static function hasColumn(string $table, string $column): bool
    {
        if ($column === 'id') {
            return in_array($table, self::index()['id'] ?? [], true);
        }

        return in_array($table, self::index()[$column] ?? [], true);
    }

    private static function index(): array
    {
        // Read once per connection (a request, a worker; each test its own).
        $conn = spl_object_id(DB::connection());
        if (self::$index !== null && self::$indexFor === $conn) {
            return self::$index;
        }
        self::$indexFor = $conn;

        $pairs = [];
        $extra = array_unique(array_merge(['id'], array_column(array_merge(self::USER_ALIASES, self::STUDENT_ALIASES, self::TEACHER_ALIASES), 1)));

        if (DB::getDriverName() === 'mysql') {
            $marks = implode(',', array_fill(0, count($extra), '?'));
            $rows  = DB::select(
                "SELECT table_name AS t, column_name AS c FROM information_schema.columns
                 WHERE table_schema = ? AND (column_name LIKE '%\\_id' OR column_name IN ($marks))",
                array_merge([DB::getDatabaseName()], $extra)
            );
            foreach ($rows as $r) {
                $pairs[] = [$r->t, $r->c];
            }
        } else {
            foreach (Schema::getTableListing() as $t) {
                $t = str_contains($t, '.') ? explode('.', $t)[1] : $t;
                foreach (Schema::getColumnListing($t) as $c) {
                    if (str_ends_with($c, '_id') || in_array($c, $extra, true)) {
                        $pairs[] = [$t, $c];
                    }
                }
            }
        }

        $index = [];
        foreach ($pairs as [$t, $c]) {
            $index[$c][] = $t;
        }

        return self::$index = $index;
    }

    /** Tests change the schema between cases. */
    public static function forgetSchema(): void
    {
        self::$index = null;
    }
}
