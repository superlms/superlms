<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Every Add / Edit slide-in of the three panels — one holding a text box, a
 * dropdown or a textarea — takes the Mark Attendance panel's look from the
 * layout: 3xl wide (wider ones kept), compact boxes with a grey focus ring (an
 * error's red border kept), small grey labels, a thin line between a body's
 * sections — lists that divide themselves (divide-y) left alone. A View
 * slide-in (nothing to fill; a hidden photo picker does not count) keeps the
 * look it always had. Only CSS: no panel's markup or code.
 */
class SlideInLookTest extends TestCase
{
    public function test_the_layout_carries_the_slide_in_look_for_add_and_edit_only(): void
    {
        $css = str_replace("\r\n", "\n", file_get_contents(resource_path('views/components/layouts/app.blade.php')));
        $panel = '#main-scroll div[class*="absolute top-0 right-0 bottom-0 w-full max-w-"]';
        $form  = $panel . ':has(:is(input:not([type="hidden"]):not([type="file"]), select, textarea))';

        $this->assertStringContainsString($form . ':is([class~="max-w-md"], [class~="max-w-lg"], [class~="max-w-xl"], [class~="max-w-2xl"]) {' . "\n" . '            max-width: 48rem;', $css);
        $this->assertStringContainsString($form . ' :is(input, select, textarea):is([class~="py-2"], [class~="py-2.5"], [class~="py-3"])', $css);
        $this->assertStringContainsString($form . ' :is(input, select, textarea):focus:not([class*="border-red"])', $css);
        $this->assertStringContainsString($form . ' label.block[class*="mb-"]', $css);
        $this->assertStringContainsString($form . ' > div[class*="overflow-y-auto"][class*="space-y"]:not([class*="divide-y"]) > :is(div, section, fieldset) + :is(div, section, fieldset):not([class*="border"]):not([class*="rounded"])', $css);

        // No rule of the block reaches a panel without fields (a View panel).
        $a = strpos($css, "/* ─── Slide-in panels (Add / Edit)");
        $block = substr($css, $a, strpos($css, '/* Sidebar logo', $a) - $a);
        $this->assertSame(substr_count($block, $panel), substr_count($block, $form));
    }
}
