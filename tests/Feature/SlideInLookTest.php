<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Every slide-in (Add / Edit / View) of the three panels takes the Mark
 * Attendance panel's look from the layout: 3xl wide (wider ones kept),
 * compact boxes with a grey focus ring (an error's red border kept), small
 * grey labels, a thin line between a body's sections — lists that divide
 * themselves (divide-y) left alone. Only CSS: no panel's markup or code.
 */
class SlideInLookTest extends TestCase
{
    public function test_the_layout_carries_the_slide_in_look(): void
    {
        $css = str_replace("
", "
", file_get_contents(resource_path('views/components/layouts/app.blade.php')));
        $panel = '#main-scroll div[class*="absolute top-0 right-0 bottom-0 w-full max-w-"]';

        $this->assertStringContainsString($panel . ':is([class~="max-w-md"], [class~="max-w-lg"], [class~="max-w-xl"], [class~="max-w-2xl"]) {' . "\n" . '            max-width: 48rem;', $css);
        $this->assertStringContainsString($panel . ' :is(input, select, textarea):is([class~="py-2"], [class~="py-2.5"], [class~="py-3"])', $css);
        $this->assertStringContainsString($panel . ' :is(input, select, textarea):focus:not([class*="border-red"])', $css);
        $this->assertStringContainsString($panel . ' label.block[class*="mb-"]', $css);
        $this->assertStringContainsString('[class*="space-y"]:not([class*="divide-y"]) > :is(div, section, fieldset) + :is(div, section, fieldset):not([class*="border"]):not([class*="rounded"])', $css);
    }
}
