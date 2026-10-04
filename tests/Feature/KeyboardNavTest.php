<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Keyboard moves through the panel's fields on every page of the three panels
 * (partials/keyboard-nav in the panels' layout), and leaves alone what handles
 * keys itself. The login / code pages (the fullscreen layout) are untouched.
 */
class KeyboardNavTest extends TestCase
{
    public function test_the_panels_layout_carries_it_and_the_login_layout_does_not(): void
    {
        $app  = file_get_contents(resource_path('views/components/layouts/app.blade.php'));
        $full = file_get_contents(resource_path('views/components/layouts/fullscreen.blade.php'));

        $this->assertStringContainsString("@include('partials.keyboard-nav')", $app);
        $this->assertStringNotContainsString('keyboard-nav', $full);
    }

    public function test_it_keeps_out_of_what_handles_keys_itself(): void
    {
        $js = file_get_contents(resource_path('views/partials/keyboard-nav.blade.php'));

        // Registered once, only on the page's own inputs and selects.
        $this->assertStringContainsString('if (window.__lmsKbNav) return;', $js);
        $this->assertStringContainsString("el.matches('input,select')", $js);
        $this->assertStringContainsString("el.closest('.lms-navbar')", $js);
        // A box with its own key handling, or marked off, is skipped.
        $this->assertStringContainsString('(x-on:|@|wire:)key(down|up|press)', $js);
        $this->assertStringContainsString('data-kb-nav', $js);
        // The last field of a form keeps Enter's own submit.
        $this->assertStringContainsString('if (!target) return;', $js);
        // Date boxes keep their arrows; text boxes only move at the cursor's edge.
        $this->assertStringContainsString("NO_CARET = { date: 1", $js);
        $this->assertStringContainsString("key === 'ArrowLeft' ? s === 0 : e === el.value.length", $js);
    }
}
