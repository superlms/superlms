<?php

namespace Tests\Unit;

use App\Support\SectionNames;
use PHPUnit\Framework\TestCase;

/**
 * The Teachers list's Class Teacher column: a class with one section reads
 * "Class 12-A" as it always has, and two or more sections of one class share
 * its line — "Class 6 A & B".
 */
class ClassTeacherLineTest extends TestCase
{
    private function row(int $standardId, ?string $class, ?string $section): object
    {
        return (object) [
            'standard_id' => $standardId,
            'standard'    => $class === null ? null : (object) ['name' => $class],
            'section'     => $section === null ? null : (object) ['name' => $section],
        ];
    }

    public function test_one_section_keeps_the_dash(): void
    {
        $this->assertSame('CLASS 12-A', SectionNames::classLine('CLASS 12', ['A']));
        $this->assertSame('Class 8-A', SectionNames::classLine('Class 8', ['Section A']));
    }

    public function test_two_or_more_sections_share_the_class(): void
    {
        $this->assertSame('CLASS 6 A & B', SectionNames::classLine('CLASS 6', ['A', 'B']));
        $this->assertSame('Class 6 A & B & C', SectionNames::classLine('Class 6', ['Section C', 'Section a', 'Section B']));
    }

    public function test_a_class_without_sections_reads_alone(): void
    {
        $this->assertSame('Nursery', SectionNames::classLine('Nursery', [null, '']));
    }

    public function test_a_teacher_s_rows_are_gathered_by_class(): void
    {
        $lines = SectionNames::classLines([
            $this->row(6, 'CLASS 6', 'A'),
            $this->row(12, 'CLASS 12', 'A'),
            $this->row(6, 'CLASS 6', 'B'),
            $this->row(3, 'Nursery', null),
            $this->row(9, null, 'C'),
        ]);

        $this->assertSame(['CLASS 6 A & B', 'CLASS 12-A', 'Nursery', '—-C'], $lines);
        $this->assertSame([], SectionNames::classLines([]));
    }
}
