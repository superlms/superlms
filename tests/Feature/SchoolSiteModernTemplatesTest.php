<?php

namespace Tests\Feature;

use App\Http\Controllers\SchoolSiteController;
use App\Livewire\Admin\WebsiteData;
use App\Livewire\SuperAdmin\SchoolWebsiteBuilder;
use App\Models\Organization;
use App\Models\SchoolWebsite;
use App\Models\User;
use App\Support\ModernSite;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Vibrant (modern, colourful) and Breeze (modern, light) — user's ask of
 * 2026-11-03: two new school-website templates on the same content, pages
 * and menu as Classic, picked per school by the Super Admin.
 */
class SchoolSiteModernTemplatesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('m', 32))]);

        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('logo')->nullable();
            $t->string('mobile_number')->nullable();
            $t->string('address')->nullable();
            $t->string('education_board')->nullable();
            $t->timestamps();
        });
        Schema::create('school_infos', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->timestamps();
        });
        Schema::create('school_websites', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id')->unique();
            $t->string('domain')->nullable()->unique();
            $t->string('template')->default('classic');
            $t->json('theme')->nullable();
            $t->json('pages')->nullable();
            $t->json('content')->nullable();
            $t->boolean('status')->default(false);
            $t->timestamps();
        });
        DB::table('organizations')->insert([
            'id' => 1, 'name' => 'Sunrise Public School', 'education_board' => 'CBSE',
            'address' => 'Fatehabad Road, Agra', 'mobile_number' => '9457293157',
        ]);
    }

    private function site(string $template, array $content = []): SchoolWebsite
    {
        $site = new SchoolWebsite([
            'organization_id' => 1, 'template' => $template, 'theme' => ['preset' => 'electric'],
            'pages' => null, 'content' => $content, 'status' => true,
        ]);
        $site->setRelation('organization', Organization::find(1)->setRelation('schoolInfo', null));

        return $site;
    }

    private function render(SchoolWebsite $site, string $path): array
    {
        $res = app(SchoolSiteController::class)->render($site, $path);

        return [$res->getStatusCode(), $res->getContent()];
    }

    private function superAdmin(): void
    {
        $su = new User(['name' => 'Super', 'email' => 'super@example.com']);
        $su->id = 900;
        $su->role = 'super-admin';
        $this->actingAs($su);
    }

    public function test_there_are_three_templates_and_the_new_two_share_their_inner_pages(): void
    {
        $this->assertSame(['classic', 'vibrant', 'breeze'], array_keys(SchoolWebsite::templates()));
        $this->assertSame(['vibrant', 'modern'], SchoolWebsite::viewFolders('vibrant'));
        $this->assertSame(['classic'], SchoolWebsite::viewFolders('classic'));
        $this->assertSame('school-site.vibrant.home', SchoolWebsite::viewFor('vibrant', 'home'));
        $this->assertSame('school-site.modern.about', SchoolWebsite::viewFor('breeze', 'about'));
        $this->assertSame('school-site.classic.about', SchoolWebsite::viewFor('classic', 'about'));
        $this->assertNull(SchoolWebsite::viewFor('classic', 'nothing-here'));
        $this->assertSame('vibrant', $this->site('vibrant')->templateKey());
    }

    public function test_every_page_renders_on_both_new_templates(): void
    {
        $content = [
            'pages' => ['labs' => ['heading' => 'Labs', 'body' => 'Our labs.', 'photos' => "https://example.com/1.jpg\nhttps://example.com/2.jpg"]],
            'results' => [['year' => '2025', 'appeared' => 120, 'passed' => 114], ['year' => '2026', 'appeared' => 130, 'passed' => 128]],
            'leadership' => [['name' => 'Dr. Meera Rao', 'role' => 'Principal', 'photo' => '', 'message' => 'Every child can shine.']],
        ];
        foreach (['vibrant' => ['v-hero', 'v-banner'], 'breeze' => ['z-hero', 'z-banner']] as $tpl => [$homeMark, $pageMark]) {
            $site = $this->site($tpl, $content);
            foreach (array_keys(SchoolWebsite::allPages()) as $slug) {
                [$status, $html] = $this->render($site, $slug === 'home' ? '' : $slug);
                $this->assertSame(200, $status, "{$tpl}/{$slug}");
                $this->assertStringContainsString('class="brand-text"', $html, "{$tpl}/{$slug}");
                $this->assertStringContainsString($slug === 'home' ? $homeMark : $pageMark, $html, "{$tpl}/{$slug}");
                $this->assertStringContainsString('id="menuList"', $html, "{$tpl}/{$slug}");
            }
            [, $labs] = $this->render($site, 'labs');
            $this->assertSame(2, preg_match_all('/class="ph[^"]*"\s+data-lb/', $labs), $tpl);

            [$status, $missing] = $this->render($site, 'no-such-page');
            $this->assertSame(404, $status);
            $this->assertStringContainsString('<b>404</b>', $missing);
        }
    }

    public function test_the_home_pages_show_the_schools_own_content(): void
    {
        $content = [
            'logo' => 'https://example.com/logo.png',
            'motto' => 'Serving Nation Through Education',
            'affiliation_no' => '2132562', 'school_code' => '61044',
            'admission_session' => '1 April – 31 July 2026',
            'hero_slides' => [
                ['image' => 'https://example.com/a.jpg', 'title' => "Agra's Leading CBSE School", 'subtitle' => 'Nursery to Class X'],
                ['image' => '', 'title' => 'Comfortable Classes', 'subtitle' => ''],
            ],
            'facilities' => [['title' => 'Robotics Lab', 'icon' => '🤖', 'desc' => 'Build and code.']],
            'results' => [['year' => '2026', 'appeared' => 100, 'passed' => 95]],
            'documents_required' => [['text' => 'Birth Certificate']],
        ];

        [, $vibrant] = $this->render($this->site('vibrant', $content), '');
        $this->assertStringContainsString('Welcome to <span class="hl">Sunrise Public School</span>', $vibrant);
        $this->assertStringContainsString('Admissions open · 1 April – 31 July 2026', $vibrant);
        $this->assertStringContainsString('CBSE Aff. No. 2132562', $vibrant);
        $this->assertSame(2, substr_count($vibrant, 'aria-roledescription="slide"'));
        $this->assertMatchesRegularExpression('/class="slide\s+is-mark"/', $vibrant);
        $this->assertStringContainsString('Robotics Lab', $vibrant);
        $this->assertStringContainsString('Birth Certificate', $vibrant);
        $this->assertStringContainsString('95%', $vibrant);
        // The call-back pill posts to the school's Website Enquiries, as a Query.
        $this->assertStringContainsString('data-kind="Call back request" data-type="query"', $vibrant);
        $this->assertStringContainsString("fetch('/api/website/school-contact'", $vibrant);

        [, $breeze] = $this->render($this->site('breeze', $content), '');
        $this->assertStringContainsString('Robotics Lab', $breeze);
        $this->assertStringContainsString('Board result', $breeze);
        $this->assertStringContainsString('Pass percentage · 2026', $breeze);
        $this->assertStringContainsString('Documents required', $breeze);   // the checklist card, from Website Data's documents
        $this->assertStringContainsString('"Serving Nation Through Education"', html_entity_decode(str_replace(['“', '”'], '"', $breeze)));
    }

    public function test_a_school_with_nothing_filled_in_still_gets_a_whole_site(): void
    {
        foreach (['vibrant', 'breeze'] as $tpl) {
            [$status, $html] = $this->render($this->site($tpl), '');
            $this->assertSame(200, $status);
            $this->assertStringNotContainsString('<img src="https://', $html, $tpl);
            $this->assertStringContainsString('aria-label="Sunrise Public School"', $html, $tpl);   // the school's seal
            $this->assertStringContainsString('Classroom', $html, $tpl);                             // the starter facilities
        }
    }

    public function test_the_documents_menu_and_document_pages_work_on_the_new_templates(): void
    {
        foreach (['vibrant', 'breeze'] as $tpl) {
            $site = $this->site($tpl, ['documents' => ['disclosures' => [
                ['title' => 'CBSE Affiliation Letter', 'file' => 'https://example.com/aff.pdf', 'date' => ''],
                ['title' => 'NOC', 'file' => '', 'date' => ''],
            ]]]);

            [, $home] = $this->render($site, '');
            $this->assertStringContainsString('href="' . url('doc/1') . '"', $home, $tpl);
            $this->assertStringNotContainsString('href="' . url('doc/2') . '"', $home, $tpl);

            [$status, $pdf] = $this->render($site, 'doc/1');
            $this->assertSame(200, $status);
            $this->assertStringContainsString('<object class="doc-embed" data="https://example.com/aff.pdf" type="application/pdf"', $pdf);
            $this->assertStringContainsString('<a class="doc-tap" href="https://example.com/aff.pdf"', $pdf);
            $this->assertSame(404, $this->render($site, 'doc/2')[0]);
        }
    }

    public function test_the_super_admin_picks_a_new_template_and_its_colours_follow(): void
    {
        $this->superAdmin();

        $page = Livewire::test(SchoolWebsiteBuilder::class, ['organization' => Organization::find(1)])
            ->call('switchTab', 'theme')
            ->assertSee(['Classic CBSE', 'Vibrant', 'Breeze'])
            ->assertSet('theme_preset', 'tricolour')
            ->call('pickTemplate', 'vibrant')
            ->assertSet('template', 'vibrant')
            ->assertSet('theme_preset', 'electric')
            ->set('template', 'breeze')                 // the card's radio
            ->assertSet('theme_preset', 'electric')
            ->set('template', 'classic')
            ->assertSet('theme_preset', 'tricolour')
            ->set('theme_preset', 'forest')             // a colour picked on purpose stays
            ->call('pickTemplate', 'breeze')
            ->assertSet('theme_preset', 'forest')
            ->set('template', 'nope')
            ->assertSet('template', 'classic')
            ->call('pickTemplate', 'vibrant')
            ->call('save');

        $site = SchoolWebsite::first();
        $this->assertSame('vibrant', $site->template);
        $this->assertSame('forest', $site->theme['preset']);

        // Each card has a drawing of its home page and says which is selected.
        $html = $page->call('switchTab', 'theme')->html();
        $this->assertSame(3, substr_count($html, 'wire:model.live="template"'));
        $this->assertStringContainsString('Selected', $html);
    }

    public function test_website_data_offers_the_same_fields_on_every_template(): void
    {
        SchoolWebsite::create(['organization_id' => 1, 'template' => 'breeze']);
        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 901;
        $admin->role = 'admin';
        $admin->organization_id = 1;
        $this->actingAs($admin);

        Livewire::test(WebsiteData::class)->call('openPage', 'details')
            ->assertSee(['School name', 'Logo', "Board's logo", 'Page banner photo']);
        Livewire::test(WebsiteData::class)->call('openPage', 'home')
            ->assertSee(['Slider', 'Our Philosophy', 'Our Location']);
        Livewire::test(WebsiteData::class)->call('openPage', 'admission')
            ->assertSee(['Admission schedule', 'Steps to apply', 'Documents required']);
    }

    public function test_the_helpers_pick_out_and_count_what_the_school_has(): void
    {
        $this->assertSame('Welcome to <span class="hl">Sunrise Public School</span>', ModernSite::highlight('Welcome to Sunrise Public School', 'Sunrise Public School'));
        $this->assertSame('Admissions Open for <span class="hl">2026 27</span>', ModernSite::highlight('Admissions Open for 2026 27'));
        $this->assertSame('&lt;b&gt; <span class="hl">&lt;i&gt; x</span>', ModernSite::highlight('<b> <i> x'));

        $res = ModernSite::results([['year' => '2026', 'appeared' => 0, 'passed' => 0], ['year' => '2025', 'appeared' => 200, 'passed' => 190]]);
        $this->assertCount(1, $res['rows']);
        $this->assertSame(95.0, $res['latest']['pct']);

        $month = ModernSite::month([['date' => now()->addDays(1)->toDateString(), 'title' => 'Sports Day']]);
        $this->assertSame(now()->addDays(1)->format('F Y'), $month['label']);
        $this->assertSame(['Sports Day'], $month['events'][now()->addDays(1)->day]);
        foreach ($month['weeks'] as $week) {
            $this->assertCount(7, $week);
        }

        $this->assertStringContainsString('--c: #2B5BF5;', ModernSite::swatch('vibrant', 0, '#2B5BF5'));
        $this->assertStringContainsString('--c: #7C3AED;', ModernSite::swatch('vibrant', 1, '#2B5BF5'));
        $this->assertStringContainsString('--t: #EEF2FF;', ModernSite::swatch('breeze', 1, '#2B5BF5'));
    }
}
