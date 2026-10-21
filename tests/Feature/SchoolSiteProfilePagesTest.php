<?php

namespace Tests\Feature;

use App\Http\Controllers\SchoolSiteController;
use App\Livewire\Admin\WebsiteData;
use App\Models\Organization;
use App\Models\SchoolWebsite;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * User's ask of 2026-10-21: the website's About Us pages — About School,
 * Mission, Vision and Values — read the School Profile; History becomes Values
 * & Goals, the profile's Values and Goals side by side; the Manager's and
 * Principal's desks move from About Us to Messaging.
 */
class SchoolSiteProfilePagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('p', 32))]);

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('logo')->nullable();
            $t->string('mobile_number')->nullable(); $t->string('address')->nullable(); $t->string('education_board')->nullable();
            $t->timestamps();
        });
        Schema::create('school_infos', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->text('about_school')->nullable();
            $t->text('usm_vision')->nullable(); $t->text('usm_mission')->nullable(); $t->text('usm_values')->nullable();
            $t->text('usm_goals')->nullable(); $t->string('school_mobile')->nullable(); $t->string('school_email')->nullable();
            $t->string('school_address')->nullable(); $t->timestamps();
        });
        Schema::create('school_management_teams', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('school_info_id'); $t->string('name')->nullable(); $t->string('designation')->nullable();
            $t->string('photo_path')->nullable(); $t->integer('sort_order')->default(0); $t->timestamps();
        });
        Schema::create('school_websites', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->unique(); $t->string('domain')->nullable()->unique();
            $t->string('template')->default('classic'); $t->json('theme')->nullable(); $t->json('pages')->nullable();
            $t->json('content')->nullable(); $t->boolean('status')->default(false); $t->timestamps();
        });

        DB::table('organizations')->insert(['id' => 1, 'name' => 'Sunrise Public School', 'education_board' => 'CBSE', 'mobile_number' => '9457293157']);
        DB::table('school_infos')->insert([
            'organization_id' => 1,
            'about_school'    => 'Sunrise has taught Agra since 1990.',
            'usm_vision'      => 'Every child a lifelong learner.',
            'usm_mission'     => '',
            'usm_values'      => "Honesty\nRespect for all",
            'usm_goals'       => 'Board results above 95%.',
        ]);

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 901;
        $admin->role = 'admin';
        $admin->organization_id = 1;
        $this->actingAs($admin);
    }

    private function site(array $attrs = []): SchoolWebsite
    {
        return SchoolWebsite::create(array_merge(['organization_id' => 1, 'status' => false], $attrs));
    }

    private function render(string $path): string
    {
        $site = SchoolWebsite::first();
        $site->setRelation('organization', Organization::with('schoolInfo')->find(1));
        $res = app(SchoolSiteController::class)->render($site, $path);
        $this->assertSame(200, $res->getStatusCode(), $path);

        return html_entity_decode(preg_replace('/\s+/', ' ', strip_tags(preg_replace('#<(style|script)\b.*?</\1>#s', '', $res->getContent()))));
    }

    public function test_the_about_us_pages_read_the_school_profile(): void
    {
        // Website Data's old texts: the profile's win where it has one.
        $this->site(['content' => [
            'about_text' => 'Old about text.', 'vision' => 'Old vision.', 'mission' => 'Our mission, as Website Data has it.',
            'pages' => ['vision' => ['body' => 'Old vision page.']],
        ]]);

        $about = $this->render('about');
        $this->assertStringContainsString('Sunrise has taught Agra since 1990.', $about);
        $this->assertStringContainsString('Our Vision Every child a lifelong learner.', $about);
        $this->assertStringContainsString('Our Values Honesty Respect for all', $about);
        $this->assertStringContainsString('Our Goals Board results above 95%.', $about);
        $this->assertStringNotContainsString('Old about text.', $about);

        $vision = $this->render('vision');
        $this->assertStringContainsString('Every child a lifelong learner.', $vision);
        $this->assertStringNotContainsString('Old vision', $vision);

        // No mission on the profile: Website Data's, until the profile has one.
        $this->assertStringContainsString('Our mission, as Website Data has it.', $this->render('mission'));
        DB::table('school_infos')->update(['usm_mission' => 'To teach with care.']);
        $this->assertStringContainsString('To teach with care.', $this->render('mission'));

        // Values & Goals: the profile's two side by side; the old History address opens it.
        $vg = $this->render('values-goals');
        $this->assertStringContainsString('VALUES & GOALS', mb_strtoupper($vg));
        $this->assertStringContainsString('Our Values Honesty Respect for all Our Goals Board results above 95%.', $vg);
        $this->assertStringContainsString('Our Values Honesty', $this->render('history'));

        DB::table('school_infos')->update(['usm_values' => null, 'usm_goals' => '']);
        $this->assertStringContainsString('Our values and goals will be published here shortly.', $this->render('values-goals'));
    }

    public function test_the_desks_sit_under_messaging(): void
    {
        $groups = SchoolWebsite::menuGroups();
        $this->assertSame(['about', 'mission', 'vision', 'values-goals', 'campus-life'], array_keys($groups['About Us']));
        $this->assertContains('manager-desk', array_keys($groups['Messaging']));
        $this->assertContains('principal-desk', array_keys($groups['Messaging']));
        $this->assertArrayNotHasKey('history', SchoolWebsite::allPages());

        $this->site();
        $home = $this->render('');
        $this->assertMatchesRegularExpression("/About Us About School Mission Vision Values & Goals Campus & School Life Messaging .*Manager's Desk Principal's Desk/", $home);
    }

    public function test_website_data_shows_the_profiles_texts_live(): void
    {
        $this->site();

        Livewire::test(WebsiteData::class)->call('openPage', 'values-goals')
            ->assertSee(['Values & Goals', 'Live', 'From your School Profile', 'Honesty', 'Board results above 95%.', 'Open School Profile']);
        Livewire::test(WebsiteData::class)->call('openPage', 'vision')
            ->assertSee(['Every child a lifelong learner.', 'Photo beside the text'])
            ->assertDontSee('Leave an empty line between paragraphs.');
        $about = Livewire::test(WebsiteData::class)->call('openPage', 'about')
            ->assertSee(['Sunrise has taught Agra since 1990.', 'Our History', 'Vision, Mission, Values and Goals show under it from your School Profile']);
        $this->assertArrayNotHasKey('about_text', $about->get('data'));

        // About Us counts Values & Goals as filled from the profile.
        $text = html_entity_decode(preg_replace('/\s+/', ' ', strip_tags(Livewire::test(WebsiteData::class)->call('openGroup', 'About Us')->html())));
        $this->assertStringContainsString('Values & Goals Filled', $text);
    }

    /** User's ask of 2026-10-21: Our Team shows the team added on the School Profile. */
    public function test_our_team_is_the_school_profiles_team(): void
    {
        // A list typed in Website Data earlier shows only while the profile has nobody.
        $this->site(['content' => ['team' => [['name' => 'Old Member', 'role' => 'Clerk', 'photo' => '']]]]);
        $this->assertStringContainsString('Old Member', $this->render('team'));

        $infoId = DB::table('school_infos')->value('id');
        DB::table('school_management_teams')->insert([
            ['school_info_id' => $infoId, 'name' => 'Meera Rao', 'designation' => 'Principal', 'photo_path' => 'https://cdn.example.com/meera.jpg', 'sort_order' => 1],
            ['school_info_id' => $infoId, 'name' => 'Arun Kumar', 'designation' => 'Manager', 'photo_path' => null, 'sort_order' => 0],
        ]);

        $team = $this->render('team');
        // In the profile's order (each photo slot carries the hidden logo watermark).
        $this->assertMatchesRegularExpression('/Arun Kumar Manager .*Meera Rao Principal/', $team);
        $this->assertStringNotContainsString('Old Member', $team);

        // Website Data's Our Team page is the profile's team, live — nothing to type there.
        $page = Livewire::test(WebsiteData::class)->call('openPage', 'team')
            ->assertSee(['Our Team', 'Live', 'From your School Profile', 'Arun Kumar', 'Meera Rao', 'Principal', 'https://cdn.example.com/meera.jpg', 'Open School Profile'])
            ->assertDontSee(['Add member', 'Old Member']);
        $this->assertSame([], $page->get('data'));
    }

    public function test_a_site_with_history_on_gets_values_and_goals(): void
    {
        $this->site(['pages' => ['home', 'about', 'history', 'contact']]);
        $migration = require base_path('database/migrations/2026_10_21_140000_history_page_is_values_and_goals.php');
        $migration->up();

        $this->assertSame(['home', 'about', 'values-goals', 'contact'], SchoolWebsite::first()->pages);
        $this->assertStringContainsString('Our Values Honesty', $this->render('history'));
    }
}
