<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Most tables were made with foreignIdFor() and no constrained(), so their
 * organization_id (and a student's / teacher's user_id) carried no index: every
 * "this school's students", "this school's homework" or "this login's details"
 * read the whole table, every school's rows, on every page and app call. These
 * indexes let each read go straight to the school's (or the login's) rows.
 *
 * Indexes only — no row and no column is changed. Each is a single column that
 * the code filters on with "=", so rows still come back in the same order as
 * before. A table that already has an index starting with the column is left as
 * it is, and one index failing is logged and skipped, never stopping the deploy.
 */
return new class extends Migration
{
    private const ORGANIZATION_TABLES = [
        'admin_enquiries', 'announcements', 'assign_teacher_standards', 'books',
        'certificates', 'chapters', 'contact_admin_students', 'contact_admin_teachers',
        'contact_super_admins', 'driver_details', 'employee_id_cards', 'exam_copies',
        'exam_subject_marks', 'home_works', 'libraries', 'mcq_options', 'mcq_questions',
        'mcq_user_answers', 'rate_lms', 'rules_and_regulations', 'school_infos', 'sections',
        'section_subjects', 'standards', 'standard_subjects', 'student_details',
        'student_id_cards', 'student_syllabi', 'subjects', 'teacher_arrangements',
        'teacher_assignments', 'teacher_availabilities', 'teacher_details', 'teacher_id_cards',
        'teacher_sections', 'teacher_subjects', 'teacher_time_tables', 'time_tables',
        'time_table_academics', 'time_table_attendees', 'time_table_locations',
        'time_table_recurrences', 'time_table_resources', 'topics', 'transfer_certificates',
        'transportations', 'transportation_students', 'users',
    ];

    /** A login's own record: looked up by user_id on every app call. */
    private const USER_TABLES = ['student_details', 'teacher_details', 'driver_details', 'user_fcm_tokens'];

    /** [table, column, index name] for every index this migration may add. */
    private function indexes(): array
    {
        $list = [];
        foreach (self::ORGANIZATION_TABLES as $table) {
            $list[] = [$table, 'organization_id', "{$table}_organization_id_lookup_index"];
        }
        foreach (self::USER_TABLES as $table) {
            $list[] = [$table, 'user_id', "{$table}_user_id_lookup_index"];
        }
        return $list;
    }

    public function up(): void
    {
        foreach ($this->indexes() as [$table, $column, $name]) {
            try {
                if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)
                    || $this->leadsAnIndex($table, $column)) {
                    continue;
                }

                Schema::table($table, fn (Blueprint $t) => $t->index($column, $name));
            } catch (\Throwable $e) {
                Log::warning("lookup index {$name} not added: " . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes() as [$table, , $name]) {
            try {
                if (Schema::hasTable($table) && Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
                }
            } catch (\Throwable $e) {
                Log::warning("lookup index {$name} not dropped: " . $e->getMessage());
            }
        }
    }

    /** Whether some index of the table already starts with this column. */
    private function leadsAnIndex(string $table, string $column): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (strtolower((string) ($index['columns'][0] ?? '')) === strtolower($column)) {
                return true;
            }
        }
        return false;
    }
};
