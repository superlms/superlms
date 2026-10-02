<?php

namespace App\Support;

/**
 * How a list reads several sections in one line: the first section's name in
 * full, then each further section by the last letter of its name, joined with
 * "&" — "Section A & B & C". A single section reads as its own name.
 */
class SectionNames
{
    /** @param iterable<string|null> $names */
    public static function joined(iterable $names): string
    {
        $unique = [];
        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name !== '' && !in_array($name, $unique, true)) {
                $unique[] = $name;
            }
        }

        if (!$unique) {
            return '';
        }

        $parts = [array_shift($unique)];
        foreach ($unique as $name) {
            $parts[] = mb_strtoupper(mb_substr($name, -1));
        }

        return implode(' & ', $parts);
    }

    /**
     * A class and the sections of it someone holds, on one line, each section
     * by the last letter of its name: one section reads "Class 8-A", several
     * "Class 6 A & B", and none the class alone.
     *
     * @param iterable<string|null> $names
     */
    public static function classLine(string $class, iterable $names): string
    {
        $letters = [];
        foreach ($names as $name) {
            $letter = mb_strtoupper(mb_substr(trim((string) $name), -1));
            if ($letter !== '' && !in_array($letter, $letters, true)) {
                $letters[] = $letter;
            }
        }
        sort($letters);

        return match (count($letters)) {
            0       => $class,
            1       => $class . '-' . $letters[0],
            default => $class . ' ' . implode(' & ', $letters),
        };
    }

    /**
     * Every class a teacher is class teacher of, a line each as classLine()
     * writes it, in the order the rows come: "Class 6 A & B", "Class 8-A".
     *
     * @param iterable<\App\Models\Teacher\AssignTeacherStandard> $assignments
     * @return string[]
     */
    public static function classLines(iterable $assignments): array
    {
        $classes = [];
        foreach ($assignments as $assigned) {
            $key = (int) $assigned->standard_id;
            $classes[$key] ??= ['name' => $assigned->standard?->name ?? '—', 'sections' => []];
            $classes[$key]['sections'][] = $assigned->section?->name;
        }

        return array_values(array_map(
            fn ($class) => self::classLine($class['name'], $class['sections']),
            $classes,
        ));
    }
}
