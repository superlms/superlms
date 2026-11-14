<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The panels' own touches (partials.panel-ui): on the Super Admin's and the
 * school admin's panels, form fields in the login page's look (the name in
 * the border) and a spinner on the button that asked the server, with a
 * press on every button; on the Super Admin's, a file dropped on any picker —
 * and the Super Admin's forms without their needless notes.
 */
class PanelUiTest extends TestCase
{
    private function layout(): string
    {
        return file_get_contents(resource_path('views/components/layouts/app.blade.php'));
    }

    private function partial(): string
    {
        return view('partials.panel-ui')->render();
    }

    public function test_it_is_on_the_super_admin_and_school_admin_panels(): void
    {
        $layout = $this->layout();

        $this->assertStringContainsString("\$lmsSuperAdmin = Auth::check() && in_array(Auth::user()->role, ['super-admin', 'sub-super-admin'], true);", $layout);
        $this->assertStringContainsString("\$lmsPanelUi    = \$lmsSuperAdmin || (Auth::check() && in_array(Auth::user()->role, ['admin', 'sub-admin'], true));", $layout);
        $this->assertStringContainsString("{{ \$lmsPanelUi ? 'lms-ui' : '' }} {{ \$lmsSuperAdmin ? 'lms-sa' : '' }}", $layout);
        $this->assertMatchesRegularExpression("/@if \(\\\$lmsPanelUi\)\s*@include\('partials\.panel-ui'\)\s*@endif/", $layout);

        // Fields and buttons wait for body.lms-ui; dropping a file for the Super Admin's body.lms-sa.
        preg_match_all('/^\s*(body[^{]*)\{/m', $this->partial(), $rules);
        $this->assertNotEmpty($rules[1]);
        foreach ($rules[1] as $selector) {
            $this->assertMatchesRegularExpression('/^body\.lms-(ui|sa)\b/', trim($selector));
        }
        $this->assertStringContainsString('body.lms-sa .sa-drop-over', $this->partial());
        $this->assertStringContainsString("if (document.body.classList.contains('lms-sa')) {", $this->partial());
    }

    public function test_fields_take_the_login_look_but_filter_bars_and_rows_keep_theirs(): void
    {
        $css = $this->partial();

        $name = 'label:not(.sr-only):not(:is(.flex, .inline-flex, .grid):not(.flex-col) > *):not(.sticky *)';
        $box  = ':is(input:not([type="checkbox"], [type="radio"], [type="file"], [type="hidden"], [type="range"], [type="color"], [type="submit"], [type="button"]), select, textarea)'
              . ':not(.text-xs, [class~="py-1"], [class~="py-0.5"], [class~="h-7"], [class~="h-8"])';

        // Any name right before its box (not only a form's first) sits on its top edge: the box is pulled up under it.
        $this->assertStringContainsString("body.lms-ui #main-scroll $name:has(+ $box) {", $css);
        $this->assertStringContainsString('margin-bottom: -.45rem !important; margin-left: .875rem !important;', $css);
        // …a rounded box with the login page's border, blue while typed in, red on an error.
        $this->assertStringContainsString("body.lms-ui #main-scroll $name + $box {", $css);
        $this->assertStringContainsString('border: 1.5px solid #9ca3af !important;', $css);
        $this->assertStringContainsString('border-radius: 9999px !important;', $css);
        $this->assertStringContainsString("$name:has(+ $box:focus) { color: #2563eb !important; }", $css);
        $this->assertStringContainsString("$name:has(+ $box" . '[class*="border-red"]) { color: #dc2626 !important; }', $css);
        // A text area is a rounded rectangle, not a pill.
        $this->assertMatchesRegularExpression('/' . preg_quote("$name + $box:is(textarea) {", '/') . '\s*border-radius: 1rem !important;/', $css);
    }

    public function test_buttons_press_and_show_a_spinner_and_a_file_can_be_dropped(): void
    {
        $js = $this->partial();

        // Dropped files go into the picker as if picked by hand, of the kinds it takes.
        $this->assertStringContainsString("const pickers = () => [...document.querySelectorAll('#main-scroll input[type=\"file\"]:not([disabled])')];", $js);
        $this->assertStringContainsString('h.input.files = dt.files;', $js);
        $this->assertStringContainsString("h.input.dispatchEvent(new Event('change', { bubbles: true }));", $js);
        $this->assertStringContainsString('(h.input.multiple ? files : files.slice(0, 1))', $js);
        $this->assertStringContainsString('if (e.defaultPrevented) { setOver(null); return; }', $js);

        // The spinner comes with a request the button started and goes with its answer.
        $this->assertStringContainsString("window.Livewire.hook('commit', ({ succeed, fail }) => {", $js);
        $this->assertStringContainsString('succeed(() => idle(el));', $js);
        $this->assertStringContainsString("el.innerHTML.includes('wire:loading')", $js);
        $this->assertStringContainsString('body.lms-ui :is(button, a[wire\:click]):not(:disabled):active { scale: .96; }', $js);
        $this->assertStringContainsString('body.lms-ui [data-sa-busy]::after {', $js);
        // Only a slow answer turns the button into a spinner: for the first .5s (the skeletons' wait) it stays as it is.
        $this->assertSame(500, \App\Support\ListSkeleton::DELAY_MS);
        $this->assertStringContainsString('transition: color 0s .5s !important;', $js);
        $this->assertStringContainsString('opacity: 0; animation: sa-spin .6s linear infinite, sa-show .12s ease .5s forwards;', $js);
    }

    public function test_a_lists_skeleton_shows_once_its_link_takes_longer_than_half_a_second(): void
    {
        $layout = $this->layout();
        $js     = $this->partial();

        // The page and, beside it, a place for the skeleton.
        $this->assertStringContainsString('<div class="relative z-10 lms-slot">', $layout);
        $this->assertStringContainsString('<div class="relative z-10 lms-skeleton" hidden></div>', $layout);
        $this->assertMatchesRegularExpression("/@if \(\\\$lmsSuperAdmin\)\s*@include\('livewire\.super-admin\.partials\.list-skeletons'\)\s*@endif/", $layout);

        // A plain click on a link to such a page shows its skeleton in the page's place; back shows the page.
        $this->assertStringContainsString("if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;", $js);
        $this->assertStringContainsString("const tpl = document.querySelector('template[data-skeleton-for=\"' + CSS.escape(url.pathname) + '\"]');", $js);
        $this->assertStringContainsString("window.addEventListener('pageshow', e => { if (e.persisted) restore(); });", $js);
        $this->assertStringContainsString('.sa-skel {', $js);
        // Not at once: only once the page has taken longer than .5s; a quicker one simply opens.
        $this->assertStringContainsString('window.__saSkelWait = setTimeout(() => {', $js);
        $this->assertStringContainsString('}, 500);', $js);
        $this->assertStringContainsString("window.addEventListener('pagehide', wait);", $js);

        // The Students, Teachers, Schools and Fees skeletons: their real column names over skeleton rows.
        $html = view('livewire.super-admin.partials.list-skeletons')->render();
        foreach ([
            'super-admin.students' => ['Students', 'Admission No', 'Father&#039;s Name'],
            'super-admin.teachers' => ['Teachers', 'Username', 'Class Teacher'],
            'super-admin.schools'  => ['Schools', 'School Code', 'Actions'],
            'super-admin.fees'     => ['Fees', 'Fee Type', 'Collection'],
        ] as $route => $words) {
            $path = parse_url(route($route), PHP_URL_PATH);
            $start = strpos($html, '<template data-skeleton-for="' . $path . '">');
            $this->assertNotFalse($start, $route);
            $tpl = substr($html, $start, strpos($html, '</template>', $start) - $start);
            foreach ($words as $word) {
                $this->assertStringContainsString($word, $tpl, "$route: $word");
            }
            $this->assertSame(10, substr_count($tpl, 'data-skeleton-row'), $route);
            $this->assertSame(4, substr_count($tpl, 'data-skeleton-card'), $route);
        }
    }

    public function test_the_super_admin_forms_have_no_needless_notes(): void
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
