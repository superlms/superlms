<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Filter bars do not ask for the section of a class that has only one:
 * partials/sole-section (in the panels' layout) picks it by itself and hides
 * the box, for every filter bar's Section box marked data-sole-section. Forms
 * and panels that pick a class and section are not marked.
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
    }

    public function test_filter_bars_are_marked_and_forms_are_not(): void
    {
        $marked = [
            'admin/student' => 'filterSection', 'admin/teacher' => 'filterSection', 'super-admin/student' => 'filterSection',
            'admin/attendance' => 'stSection', 'accounts/attendance' => 'stSection', 'admin/performance' => 'perfSection',
            'admin/report-card' => 'filterSection', 'partials/view-fee-header' => 'viewClassSectionId', 'admin/lists' => 'sectionId',
        ];
        foreach ($marked as $view => $prop) {
            $this->assertMatchesRegularExpression('/<select\b[^>]*data-sole-section wire:model\.live="' . $prop . '"/', $this->bladeOf($view), "$view $prop");
        }

        $unmarked = [
            'admin/attendance' => 'sMarkSection', 'admin/performance' => 'uploadSection', 'admin/report-card' => 'issueSection',
            'admin/admit-card' => 'genSection', 'admin/time-table' => 'createSectionId', 'super-admin/fees' => 'updateSectionId',
        ];
        foreach ($unmarked as $view => $prop) {
            $this->assertDoesNotMatchRegularExpression('/<select\b[^>]*data-sole-section[^>]*wire:model\.live="' . $prop . '"/', $this->bladeOf($view), "$view $prop");
        }

        $this->assertStringContainsString('<div data-sole-section-wrap>', $this->bladeOf('admin/lists'));
    }
}
