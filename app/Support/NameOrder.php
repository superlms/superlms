<?php

namespace App\Support;

use App\Models\Student\StudentDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

/**
 * Every list of students or teachers reads A to Z by name — the panels, the
 * apps, the exports and the PDFs alike. Ties (two of the same name) keep the
 * order they were added in.
 */
class NameOrder
{
    /**
     * Students A–Z: a student_details query (or one joined to it) ordered by
     * the student's full name.
     */
    public static function students(EloquentBuilder|QueryBuilder $query, string $table = 'student_details'): EloquentBuilder|QueryBuilder
    {
        return $query->orderBy("{$table}.full_name")->orderBy("{$table}.id");
    }

    /**
     * Rows that belong to a student (admit cards, report cards, TCs…) A–Z by
     * that student's full name: a query on a table holding a student_details id.
     */
    public static function byStudent(EloquentBuilder|QueryBuilder $query, string $table, string $studentColumn = 'student_detail_id'): EloquentBuilder|QueryBuilder
    {
        return $query
            ->orderBy(static::studentName($table, $studentColumn))
            ->orderBy("{$table}.id");
    }

    /**
     * The student's full name as a subquery to order by, for a table holding a
     * student_details id — when a row's own order should follow the name
     * (several payments of one student, newest first).
     */
    public static function studentName(string $table, string $studentColumn = 'student_detail_id'): EloquentBuilder
    {
        return StudentDetail::select('full_name')
            ->whereColumn('student_details.id', "{$table}.{$studentColumn}")
            ->limit(1);
    }

    /**
     * Teachers (or anyone whose name sits on users) A–Z: a query on a table
     * that holds a users id, ordered by that user's name.
     */
    public static function byUser(EloquentBuilder|QueryBuilder $query, string $table = 'teacher_details', string $userColumn = 'user_id'): EloquentBuilder|QueryBuilder
    {
        return $query
            ->orderBy(User::select('name')->whereColumn('users.id', "{$table}.{$userColumn}")->limit(1))
            ->orderBy("{$table}.id");
    }

    /** Teachers A–Z: a teacher_details query ordered by the teacher's name. */
    public static function teachers(EloquentBuilder|QueryBuilder $query, string $table = 'teacher_details'): EloquentBuilder|QueryBuilder
    {
        return static::byUser($query, $table);
    }

    /**
     * A–Z on a collection already loaded, by the name the callback gives
     * (case and surrounding spaces ignored, numbers in names read as numbers).
     */
    public static function sort(Collection $items, callable $name): Collection
    {
        return $items
            ->sortBy(fn ($item) => mb_strtolower(trim((string) $name($item))), SORT_NATURAL)
            ->values();
    }

    /** The same A–Z on a plain array of rows; keys are not kept. */
    public static function sortArray(array $rows, callable $name): array
    {
        return static::sort(collect($rows), $name)->all();
    }
}
