<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The Super Admin panel's own touches (partials.super-admin-ui), on every page
 * of it and nowhere else: form fields in the login page's look (the name in
 * the border), a file dropped on any picker, and a spinner on the button that
 * asked the server — and the forms without their needless notes.
 */
class SuperAdminUiTest extends TestCase
{
    private function layout(): string
    {
        return file_get_contents(resource_path('views/components/layouts/app.blade.php'));
    }

    private function partial(): string
    {
        return file_get_contents(resource_path('views/partials/super-admin-ui.blade.php'));
    }

    public function test_it_is_on_the_super_admin_panel_only(): void
    {
        $layout = $this->layout();

        $this->assertStringContainsString("\$lmsSuperAdmin = Auth::check() && in_array(Auth::user()->role, ['super-admin', 'sub-super-admin'], true);", $layout);
        $this->assertStringContainsString("{{ \$lmsSuperAdmin ? 'lms-sa' : '' }}", $layout);
        $this->assertMatchesRegularExpression("/@if \(\\\$lmsSuperAdmin\)\s*@include\('partials\.super-admin-ui'\)\s*@endif/", $layout);

        // Every rule and listener of it waits for body.lms-sa or #main-scroll.
        preg_match_all('/^\s*(body[^{]*)\{/m', $this->partial(), $rules);
        $this->assertNotEmpty($rules[1]);
        foreach ($rules[1] as $selector) {
            $this->assertStringStartsWith('body.lms-sa', trim($selector));
        }
    }

    public function test_fields_take_the_login_look(): void
    {
        $css = $this->partial();

        // Any label right before a text box, dropdown or text area (not only a
        // form's first) sits on its top edge: the box is pulled up under it.
        $this->assertStringContainsString('body.lms-sa #main-scroll label:has(+ :is(input:not([type="checkbox"], [type="radio"], [type="file"], [type="hidden"]', $css);
        $this->assertStringContainsString('margin-bottom: -.45rem !important; margin-left: .875rem !important;', $css);
        $this->assertStringContainsString('position: relative; z-index: 2;', $css);
        $this->assertStringNotContainsString('label:first-child', $css);
        // …of a rounded box with the login page's border, blue while typed in, red on an error.
        $this->assertStringContainsString('border: 1.5px solid #9ca3af !important;', $css);
        $this->assertStringContainsString('border-radius: 9999px !important;', $css);
        $this->assertStringContainsString(':focus) { color: #2563eb !important; }', $css);
        $this->assertStringContainsString('[class*="border-red"]) { color: #dc2626 !important; }', $css);
    }

    public function test_a_file_can_be_dropped_on_a_picker_and_buttons_show_a_spinner(): void
    {
        $js = $this->partial();

        // Dropped files go into the picker as if picked by hand, of the kinds it takes.
        $this->assertStringContainsString("const pickers = () => [...document.querySelectorAll('#main-scroll input[type=\"file\"]:not([disabled])')];", $js);
        $this->assertStringContainsString('h.input.files = dt.files;', $js);
        $this->assertStringContainsString("h.input.dispatchEvent(new Event('change', { bubbles: true }));", $js);
        $this->assertStringContainsString("(h.input.multiple ? files : files.slice(0, 1))", $js);
        // A drop area of a page's own is left to itself.
        $this->assertStringContainsString('if (e.defaultPrevented) { setOver(null); return; }', $js);

        // The spinner comes with a request the button started and goes with its answer.
        $this->assertStringContainsString("window.Livewire.hook('commit', ({ succeed, fail }) => {", $js);
        $this->assertStringContainsString('succeed(() => idle(el));', $js);
        $this->assertStringContainsString("el.innerHTML.includes('wire:loading')", $js);
        $this->assertStringContainsString('body.lms-sa :is(button, a[wire\:click]):not(:disabled):active { scale: .96; }', $js);
    }

    public function test_the_forms_have_no_needless_notes(): void
    {
        $views = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views/livewire/super-admin')));
        foreach ($views as $file) {
            if (!str_ends_with($file, '.blade.php')) {
                continue;
            }
            $blade = file_get_contents($file);
            $name  = str_replace(resource_path('views/'), '', (string) $file);

            // No "(optional)" / "(Optional, max 1 MB)" beside a field's name.
            $this->assertDoesNotMatchRegularExpression('/<label\b[^>]*>[^<]*(<span[^>]*>\*<\/span>)?\s*(<span[^>]*>)?\s*\((optional|Optional)/', $blade, $name);
            foreach (['JPG / PNG, up to', 'JPG/PNG · max 1 MB', 'auto-fetched from the class', 'Used to group and filter schools in the listing',
                      'If set, tapping the notification opens this screen', 'Separate several with commas.'] as $note) {
                $this->assertStringNotContainsString($note, $blade, "$name: $note");
            }
        }
    }
}
