<?php

namespace Tests\Feature;

use App\Http\Controllers\SchoolSiteController;
use App\Livewire\Admin\WebsiteData;
use App\Livewire\SuperAdmin\SchoolWebsiteBuilder;
use App\Models\Organization;
use App\Models\SchoolWebsite;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Classic CBSE template (user's ask of 2026-10-16, after nrips.in): the
 * Super Admin picks it per school, the school's Website Data save leaves the
 * pick alone, and the site shows the strip and masthead details, a slider
 * whose photo-less slides carry the logo watermark, and a Documents menu of
 * the uploaded disclosures, each on a page of its own.
 */
class SchoolSiteClassicTemplateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('c', 32))]);

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

    private function site(array $content, string $template = 'classic'): SchoolWebsite
    {
        $site = new SchoolWebsite([
            'organization_id' => 1, 'template' => $template, 'theme' => ['preset' => 'tricolour'],
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

    public function test_the_super_admin_picks_classic_and_its_colours_follow(): void
    {
        $su = new User(['name' => 'Super', 'email' => 'super@example.com']);
        $su->id = 900;
        $su->role = 'super-admin';
        $this->actingAs($su);

        Livewire::test(SchoolWebsiteBuilder::class, ['organization' => Organization::find(1)])
            ->call('switchTab', 'theme')
            ->assertSee('Classic CBSE')
            ->call('pickTemplate', 'classic')
            ->assertSet('theme_preset', 'tricolour')
            ->call('pickTemplate', 'nope')
            ->assertSet('template', 'classic')
            ->call('save');

        $site = SchoolWebsite::first();
        $this->assertSame('classic', $site->template);
        $this->assertSame('tricolour', $site->theme['preset']);
    }

    /**
     * The builder in the Modules look (user's ask of 2026-10-16): pages ticked
     * in a list with Enable all / Disable all and what is on, the template a
     * radio, Publish a switch, and Save at the foot of each tab.
     */
    public function test_the_builder_reads_like_the_modules_tab(): void
    {
        $su = new User(['name' => 'Super', 'email' => 'super@example.com']);
        $su->id = 900;
        $su->role = 'super-admin';
        $this->actingAs($su);
        SchoolWebsite::create(['organization_id' => 1, 'pages' => ['home', 'about', 'contact']]);
        SchoolWebsite::flushEventListeners();   // going live tells the super admins; no users table here
        $all = count(SchoolWebsite::allPages());

        $page = Livewire::test(SchoolWebsiteBuilder::class, ['organization' => Organization::find(1)]);
        $html = $page->html();
        $this->assertStringContainsString('Enable all', $html);
        $this->assertStringContainsString("3 of {$all} on", $html);
        $this->assertStringContainsString('Save Pages', $html);
        $this->assertMatchesRegularExpression('/wire:model="pages" value="about"\s+checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/wire:model="pages" value="history"\s+checked/', $html);

        $page->call('enableAllPages')->assertSee("{$all} of {$all} on")
            ->call('disableAllPages')->assertSee("1 of {$all} on")
            ->set('pages', ['home', 'about', 'result'])
            ->call('switchTab', 'theme')->assertSee('Save Template &amp; Theme', false)
            ->set('template', 'classic')->assertSet('theme_preset', 'tricolour')
            ->call('switchTab', 'domain')->assertSee('Save Domain &amp; Publish', false)
            ->set('domain', 'sunrise.edu.in')
            ->toggle('status')   // the switch's wire:click="$toggle('status')"
            ->call('save');

        $site = SchoolWebsite::first();
        $this->assertSame(['home', 'about', 'result'], $site->pages);
        $this->assertSame('classic', $site->template);
        $this->assertSame('sunrise.edu.in', $site->domain);
        $this->assertTrue($site->status);
    }

    public function test_saving_website_data_keeps_the_template_and_the_slides(): void
    {
        SchoolWebsite::create(['organization_id' => 1, 'template' => 'classic']);

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 901;
        $admin->role = 'admin';
        $admin->organization_id = 1;
        $this->actingAs($admin);

        Livewire::test(WebsiteData::class)
            ->call('openPage', 'home')
            ->call('addRow', 'hero_slides')
            ->set('data.hero_slides.0.title', 'Welcome to Sunrise')
            ->call('save')
            ->call('openPage', 'details')
            ->set('data.board_logo', 'https://example.com/cbse.png')
            ->call('save')
            ->call('openPage', 'result')
            ->call('addRow', 'results')
            ->set('data.results.0.year', '2025')
            ->call('save');

        $site = SchoolWebsite::first();
        $this->assertSame('classic', $site->template);
        $this->assertSame('Welcome to Sunrise', $site->content['hero_slides'][0]['title']);
        $this->assertSame('https://example.com/cbse.png', $site->content['board_logo']);
        $this->assertSame('2025', $site->content['results'][0]['year']);
    }

    public function test_the_strip_masthead_and_slider(): void
    {
        [$status, $html] = $this->render($this->site([
            'logo' => 'https://example.com/logo.png',
            'motto' => 'Serving Nation Through Education',
            'medium' => 'An English Medium Co-Educational CBSE School',
            'affiliation_no' => '2132562', 'school_code' => '61044',
            'youtube' => 'https://youtube.com/@sunrise',
            'hero_slides' => [
                ['image' => 'https://example.com/a.jpg', 'title' => "Agra's Leading CBSE School", 'subtitle' => 'Nursery to Class X'],
                ['image' => '', 'title' => 'Comfortable Classes', 'subtitle' => ''],
            ],
        ]), '');

        $this->assertSame(200, $status);
        $this->assertStringContainsString('CBSE Aff. No. 2132562', $html);
        $this->assertStringContainsString('School Code: 61044', $html);
        $this->assertStringContainsString('>YouTube</a>', $html);
        $this->assertStringContainsString('class="mast-name">Sunrise Public School</a>', $html);
        $this->assertStringContainsString('"Serving Nation Through Education"', $html);
        $this->assertStringContainsString('An English Medium Co-Educational CBSE School', $html);
        // No board logo set → the board's seal.
        $this->assertStringContainsString('aria-label="Affiliated to CBSE"', $html);
        // Two slides; the one without a photo is marked for the watermark.
        $this->assertSame(2, substr_count($html, 'aria-roledescription="slide"'));
        $this->assertMatchesRegularExpression('/class="slide on\s+"/', $html);
        $this->assertMatchesRegularExpression('/class="slide\s+is-mark"[^>]*>\s*<div class="wm"/', $html);
        $this->assertStringContainsString('--wm-tile:url(&quot;https://example.com/logo.png&quot;)', $html);
    }

    public function test_a_school_without_slides_or_logo_gets_watermark_slides_and_a_seal(): void
    {
        [$status, $html] = $this->render($this->site([]), '');

        $this->assertSame(200, $status);
        $this->assertStringNotContainsString('<img src="https://', $html);
        $this->assertMatchesRegularExpression('/class="slide on is-mark"/', $html);
        $this->assertStringContainsString('aria-label="Sunrise Public School"', $html);   // the school's seal
        $this->assertStringContainsString('Welcome to Sunrise Public School', $html);
    }

    public function test_the_documents_menu_lists_uploaded_disclosures_each_on_its_own_page(): void
    {
        $site = $this->site(['documents' => ['disclosures' => [
            ['title' => 'CBSE Affiliation Letter', 'file' => 'https://example.com/aff.pdf', 'date' => ''],
            ['title' => 'NOC', 'file' => '', 'date' => ''],
            ['title' => 'Inspection Video', 'file' => 'https://youtu.be/aqz-KE-bpKQ', 'date' => ''],
        ]]]);

        [, $home] = $this->render($site, '');
        $this->assertStringContainsString('href="' . url('doc/1') . '"', $home);
        $this->assertStringContainsString('href="' . url('doc/3') . '"', $home);
        $this->assertStringNotContainsString('href="' . url('doc/2') . '"', $home);

        [$status, $pdf] = $this->render($site, 'doc/1');
        $this->assertSame(200, $status);
        $this->assertStringContainsString('<object data="https://example.com/aff.pdf" type="application/pdf"', $pdf);
        $this->assertStringContainsString('Click to Download', $pdf);

        [, $video] = $this->render($site, 'doc/3');
        $this->assertStringContainsString('youtube-nocookie.com/embed/aqz-KE-bpKQ', $video);

        $this->assertSame(404, $this->render($site, 'doc/2')[0]);   // not uploaded
        $this->assertSame(404, $this->render($site, 'doc/9')[0]);
    }

    public function test_every_classic_page_renders_and_classic_is_the_only_template(): void
    {
        $site = $this->site(['pages' => ['labs' => ['heading' => 'Labs', 'body' => 'Our labs.', 'photos' => "https://example.com/1.jpg\nhttps://example.com/2.jpg"]]]);
        foreach (array_keys(SchoolWebsite::allPages()) as $slug) {
            [$status, $html] = $this->render($site, $slug === 'home' ? '' : $slug);
            $this->assertSame(200, $status, $slug);
            $this->assertStringContainsString('class="mast-name"', $html, $slug);
        }
        [, $labs] = $this->render($site, 'labs');
        $this->assertSame(2, preg_match_all('/class="ph[^"]*"\s+data-lb/', $labs));

        // Playful (kider) is gone (user's ask of 2026-10-20): a site naming it, or
        // any other, is shown on Classic.
        $this->assertSame(['classic'], array_keys(SchoolWebsite::templates()));
        foreach (['kider', 'unknown-template'] as $name) {
            SchoolWebsite::query()->delete();
            $old = $this->site([], $name);
            $this->assertSame('classic', $old->templateKey());
            [$status, $html] = $this->render($old, 'query');
            $this->assertSame(200, $status);
            $this->assertStringContainsString('class="mast-name"', $html);
        }
    }

    /**
     * The Super Admin ticks pages under the menus the site shows them in
     * (user's ask of 2026-10-16): Classic's About Us holds what its About Us
     * menu holds, in that order.
     */
    public function test_the_builder_lists_pages_under_the_site_menu_of_its_template(): void
    {
        $su = new User(['name' => 'Super', 'email' => 'super@example.com']);
        $su->id = 900;
        $su->role = 'super-admin';
        $this->actingAs($su);
        SchoolWebsite::create(['organization_id' => 1, 'template' => 'classic']);

        $text = fn ($page) => preg_replace('/\s+/', ' ', strip_tags($page->html()));
        $page = Livewire::test(SchoolWebsiteBuilder::class, ['organization' => Organization::find(1)]);

        $this->assertStringContainsString(
            "About Us About School History Mission Vision Manager's Desk Principal's Desk Campus & School Life Messaging Chairman's Desk",
            html_entity_decode($text($page))
        );
        $this->assertStringContainsString('Photogallery Photo Gallery Sports & Activities Building Photos', html_entity_decode($text($page)));
        $this->assertStringContainsString('Documents Fills itself', $text($page));
        $this->assertSame(
            ['about', 'history', 'mission', 'vision', 'manager-desk', 'principal-desk', 'campus-life'],
            array_keys(SchoolWebsite::menuGroups('classic')['About Us'])
        );
        // Every page is listed exactly once.
        $listed = array_merge(...array_map('array_keys', array_values(SchoolWebsite::menuGroups('classic'))));
        $this->assertEqualsCanonicalizing(array_keys(SchoolWebsite::allPages()), $listed);

        // Classic is the only template there is to pick.
        $page->call('switchTab', 'theme')->assertSee('Classic CBSE')->assertDontSee('Playful');
    }

    /**
     * The site follows the school's own logo (user's report of 2026-10-16):
     * Website Data had saved a copy of the profile's logo — an address on the
     * old CloudFront, dead — and the site kept showing the copy.
     */
    public function test_the_site_shows_the_profile_logo_not_a_stale_copy(): void
    {
        $now  = 'https://superlms-media.s3.ap-south-1.amazonaws.com/admin/profile/photos/new.png';
        $dead = 'https://dvtmf2beaqewz.cloudfront.net/superadmin/schools/logos/old.png';
        DB::table('organizations')->where('id', 1)->update(['logo' => $now, 'email' => 'office@sunrise.in']);

        // The data as it was left on the live site: copies of the profile.
        DB::table('school_websites')->insert([
            'organization_id' => 1, 'template' => 'classic', 'status' => true,
            'content' => json_encode([
                'logo' => $dead, 'school_name' => 'Sunrise Public School', 'email' => 'office@sunrise.in',
                'board' => 'CBSE', 'motto' => 'Serving Nation Through Education', 'team' => [],
            ]),
        ]);
        (require database_path('migrations/2026_10_16_180000_school_website_content_follows_the_profile.php'))->up();

        $site = SchoolWebsite::first();
        $this->assertSame(['motto' => 'Serving Nation Through Education'], $site->content);
        $site->setRelation('organization', Organization::find(1)->setRelation('schoolInfo', null));
        [, $html] = $this->render($site, '');
        $this->assertStringContainsString('<img src="' . $now . '" alt="Sunrise Public School logo"', $html);
        $this->assertStringNotContainsString('cloudfront', $html);

        // A logo the school typed in on purpose stays, with the profile's as
        // the stand-in should its link fail.
        $own = $this->site(['logo' => 'https://cdn.sunrise.in/crest.png']);
        $own->organization->logo = $now;
        [, $html] = $this->render($own, '');
        $this->assertMatchesRegularExpression('#<img src="https://cdn\.sunrise\.in/crest\.png" alt="Sunrise Public School logo"\s+data-alt="' . preg_quote($now, '#') . '"#', $html);
    }

    public function test_saving_website_data_stores_only_what_differs_from_the_profile(): void
    {
        DB::table('organizations')->where('id', 1)->update(['logo' => 'https://bucket.example/admin/profile/photos/a.png']);
        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 901;
        $admin->role = 'admin';
        $admin->organization_id = 1;
        $this->actingAs($admin);

        Livewire::test(WebsiteData::class)
            ->call('openPage', 'details')
            ->assertSet('data.logo', 'https://bucket.example/admin/profile/photos/a.png')   // shown, as before
            ->set('data.motto', 'Learn to Serve')
            ->call('save');

        $content = SchoolWebsite::first()->content;
        $this->assertSame('Learn to Serve', $content['motto']);
        foreach (['logo', 'school_name', 'phone', 'address', 'board', 'hero_title', 'team'] as $copied) {
            $this->assertArrayNotHasKey($copied, $content, $copied);
        }

        // The profile's logo changes; the site follows.
        DB::table('organizations')->where('id', 1)->update(['logo' => 'https://bucket.example/admin/profile/photos/b.png']);
        $site = SchoolWebsite::first()->load('organization');
        $this->assertSame('https://bucket.example/admin/profile/photos/b.png', $site->resolvedContent()['logo']);
    }
}
