<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Nothing asks for the section of a class that has only one:
 * partials/sole-section (in the panels' layout) picks it by itself and hides
 * the box for every Section box a section has to be picked in (filter bars
 * and forms, marked data-sole-section), and only hides the box — keeping
 * "All sections" — where the empty choice means all of them (marked
 * data-sole-section="keep-all"). A labelled field's wrapper goes with it.
 */
class SoleSectionFilterTest extends TestCase
{
    private function bladeOf(string $name): string
    {
        return file_get_contents(resource_path('views/livewire/' . $name . '.blade.php'));
    }

    public function test_the_panels_layout_carries_it(): void
    {
        $app = file_get_contents(resource_path('views/components/layouts/app.blade.php'));
        $this->assertStringContainsString("@include('partials.sole-section')", $app);

        $js = file_get_contents(resource_path('views/partials/sole-section.blade.php'));
        // Picked the way a hand pick is, once Livewire listens, after every update.
        $this->assertStringContainsString("el.dispatchEvent(new Event('change', { bubbles: true }))", $js);
        $this->assertStringContainsString("window.Livewire.hook('morphed'", $js);
        $this->assertStringContainsString("'livewire:initialized'", $js);
        // A disabled box, or one with several sections, is left alone.
        $this->assertStringContainsString('real.length === 1 && !el.disabled', $js);
        // An "All sections" box is only hidden: its value is never set.
        $this->assertStringContainsString("el.getAttribute('data-sole-section') !== 'keep-all' && el.value !== only.value", $js);
    }

    public function test_section_boxes_that_must_be_picked_pick_the_sole_section(): void
    {
        $picked = [
            // Filter bars.
            ['admin/student', 'filterSection'], ['admin/teacher', 'filterSection'], ['super-admin/student', 'filterSection'],
            ['admin/attendance', 'stSection'], ['accounts/attendance', 'stSection'], ['admin/performance', 'perfSection'],
            ['admin/report-card', 'filterSection'], ['partials/view-fee-header', 'viewClassSectionId'], ['admin/lists', 'sectionId'],
            // Forms.
            ['admin/attendance', 'sMarkSection'], ['accounts/attendance', 'sMarkSection'], ['admin/performance', 'uploadSection'],
            ['admin/report-card', 'issueSection'], ['admin/time-table', 'createSectionId'], ['admin/exam-copy', 'uploadSection'],
            ['admin/student', 'studentsSection'], ['admin/_partials/student-picker', 'sectionId'],
        ];
        foreach ($picked as [$view, $prop]) {
            $this->assertMatchesRegularExpression('/<select\b[^>]*data-sole-section wire:model(\.live|\.defer)?="' . $prop . '"/', $this->bladeOf($view), "$view $prop");
        }
    }

    public function test_all_sections_boxes_are_only_hidden(): void
    {
        $kept = [
            ['admin/admit-card', 'genSection'], ['admin/admit-card', 'printSection'], ['accounts/admit-card', 'genSection'],
            ['accounts/admit-card', 'printSection'], ['admin/push-notification', 'sectionId'], ['admin/messaging', 'sectionId'],
            ['admin/student', 'exportSection'], ['super-admin/student', 'exportSection'], ['admin/id-card', 'exportSection'],
            ['admin/seating-plan', 'dsSectionId'], ['admin/syllabus', 'chapterSectionId'], ['admin/add-exam', 'paperSection'],
            ['admin/custom-fee', 'paySectionId'], ['partials/fee-concession-panel', 'concFilterSection'],
            ['partials/fee-structure-panel', 'duesSectionId'], ['super-admin/fees', 'updateSectionId'],
        ];
        foreach ($kept as [$view, $prop]) {
            $this->assertMatchesRegularExpression('/<select\b[^>]*data-sole-section="keep-all" wire:model\.live="' . $prop . '"/', $this->bladeOf($view), "$view $prop");
        }
    }

    public function test_a_labelled_field_hides_with_its_label(): void
    {
        foreach (['admin/lists', 'admin/_partials/student-picker', 'admin/exam-copy', 'admin/push-notification', 'admin/admit-card', 'super-admin/fees'] as $view) {
            $this->assertMatchesRegularExpression('/<div data-sole-section-wrap>\s*<label\b[^>]*>Section/', $this->bladeOf($view), $view);
        }
    }

    public function test_no_section_box_is_left_unmarked(): void
    {
        // Every <select> bound to a section in the panels carries one of the two marks,
        // except Add Exam's syllabus picker, which shows no box for a class of one section.
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views/livewire')));
        $missing = [];
        foreach ($files as $file) {
            if (!str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            preg_match_all('/<select\b[^>]*wire:model(?:\.live|\.defer)?="([A-Za-z.]*[sS]ection[A-Za-z]*)"/', file_get_contents($file->getPathname()), $m, PREG_SET_ORDER);
            foreach ($m as [$tag, $prop]) {
                if (!str_contains($tag, 'data-sole-section') && $prop !== 'sylModalSectionId') {
                    $missing[] = $file->getFilename() . ' ' . $prop;
                }
            }
        }
        $this->assertSame([], $missing);
    }
}
