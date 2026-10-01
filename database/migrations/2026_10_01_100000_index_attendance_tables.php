<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The attendance tables had no index but their id, so every "this student's
 * attendance" or "this school's day" read the whole table. Analytics counts
 * each student's attendance in one query: for a school of ~700 students that
 * took over a minute, the web server gave up at 60 seconds and the page never
 * opened. With these the same page loads in about a second.
 *
 * Indexes only — no row is changed.
 */
return new class extends Migration
{
    private const INDEXES = [
        'student_attendances' => [
            'student_attendances_student_date_index' => ['student_detail_id', 'attendance_date'],
            'student_attendances_org_date_index'     => ['organization_id', 'attendance_date'],
        ],
        'teacher_attendances' => [
            'teacher_attendances_teacher_date_index' => ['teacher_detail_id', 'attendance_date'],
            'teacher_attendances_org_date_index'     => ['organization_id', 'attendance_date'],
        ],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $name => $columns) {
                if (Schema::hasIndex($table, $name)
                    || array_filter($columns, fn ($column) => !Schema::hasColumn($table, $column))) {
                    continue;
                }

                Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach (array_keys($indexes) as $name) {
                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
                }
            }
        }
    }
};
