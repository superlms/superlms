<?php

namespace App\Support;

use App\Models\Admin\TeacherTimeTable;
use App\Models\Student\Section;
use App\Models\Student\SectionSubject;
use App\Models\Student\Standard;
use App\Models\Student\StandardSubject;
use App\Models\Teacher\TeacherAssignment;

/**
 * A subject edited into another class moves there, for the admin panel and the
 * mobile API alike: it leaves the class it was edited from — that class's link
 * and its sections' links — so it lists under the new class only. A subject
 * shared with further classes keeps those.
 */
class SubjectMove
{
    /**
     * Why the subject can't leave $fromStandardId — a timetable or teacher
     * assignment of that class still uses it — or null when it can.
     */
    public static function blocker(int $subjectId, int $fromStandardId): ?string
    {
        $used = TeacherTimeTable::where('subject_id', $subjectId)->where('standard_id', $fromStandardId)->exists()
            || TeacherAssignment::where('subject_id', $subjectId)->where('standard_id', $fromStandardId)->exists();

        if (!$used) {
            return null;
        }

        $class = Standard::find($fromStandardId)?->name ?? 'its current class';

        return "This subject is used in the timetable or assignments of {$class}. Remove it there first, then move it.";
    }

    /** Take the subject out of $fromStandardId and that class's sections. */
    public static function leave(int $subjectId, int $fromStandardId): void
    {
        StandardSubject::where('subject_id', $subjectId)->where('standard_id', $fromStandardId)->delete();
        SectionSubject::where('subject_id', $subjectId)->where('standard_id', $fromStandardId)->delete();
    }

    /** Those of $sectionIds that are sections of $standardId. */
    public static function sectionsIn(array $sectionIds, int $standardId): array
    {
        return Section::whereIn('id', array_unique(array_map('intval', $sectionIds)))
            ->where('standard_id', $standardId)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
