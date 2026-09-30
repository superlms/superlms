<?php

namespace App\Support;

use App\Models\Student\Section;

/**
 * Which section a student belongs in, given their class.
 *
 * A section is kept when it is one of the class's own. Otherwise — none
 * picked, or a section of some other class (its "A" reads the same in a list,
 * but the class's edit form and section filter never find it) — the student
 * goes into the class's section of the same name, or into the class's only
 * section when it has just one. Anything else is left as it was, for a person
 * to pick.
 *
 * The migration fix_student_sections applied the same rule to the students
 * already saved; it carries its own copy so it never changes after it has run.
 */
class StudentSection
{
    public static function resolve($standardId, $sectionId): ?int
    {
        $sectionId = $sectionId ? (int) $sectionId : null;

        if (! $standardId) {
            return $sectionId;
        }

        $own = Section::where('standard_id', (int) $standardId)->get(['id', 'name']);

        if ($sectionId && $own->contains('id', $sectionId)) {
            return $sectionId;
        }

        if ($sectionId) {
            $name = self::key(Section::whereKey($sectionId)->value('name'));
            $same = $name === '' ? null : $own->first(fn ($s) => self::key($s->name) === $name);
            if ($same) {
                return (int) $same->id;
            }
        }

        if ($own->count() === 1) {
            return (int) $own->first()->id;
        }

        return $sectionId;
    }

    private static function key(?string $name): string
    {
        return mb_strtolower(trim((string) $name));
    }
}
