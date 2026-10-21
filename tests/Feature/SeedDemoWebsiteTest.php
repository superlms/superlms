<?php

namespace Tests\Feature;

use App\Http\Controllers\SchoolSiteController;
use App\Models\Organization;
use App\Models\SchoolWebsite;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * User's ask of 2026-10-21: fill The Demo School's website with every page's
 * data at once, to see how the site looks with real content
 * (lms:seed-demo-website --demo).
 */
class SeedDemoWebsiteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('d', 32))]);
        Carbon::setTestNow('2026-10-21 10:00:00');
        Storage::fake('s3');

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
        Schema::create('rules_and_regulations', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->default(0); $t->unsignedBigInteger('user_id')->default(0);
            $t->json('content')->nullable(); $t->timestamps();
        });
        Schema::create('transportations', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('route_name'); $t->string('vehicle_type')->nullable();
            $t->string('route_group')->nullable(); $t->unsignedBigInteger('driver_detail_id')->default(0);
            $t->string('pickup_time')->nullable(); $t->string('drop_time')->nullable(); $t->string('pickup_location')->nullable();
            $t->string('drop_location')->nullable(); $t->json('stops')->nullable();
            $t->decimal('monthly_fee', 10, 2)->default(0); $t->unsignedSmallInteger('capacity')->default(0);
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('time_tables', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('created_by')->nullable();
            $t->string('title'); $t->text('description')->nullable(); $t->date('date')->nullable();
            $t->time('start_time')->nullable(); $t->time('end_time')->nullable(); $t->string('event_type')->default('class');
            $t->string('color')->nullable(); $t->string('attachment')->nullable(); $t->string('recurrence')->nullable();
            $t->date('recurrence_end_date')->nullable(); $t->boolean('is_all_day')->default(false);
            $t->boolean('is_cancelled')->default(false); $t->text('cancellation_reason')->nullable(); $t->timestamps();
        });
        Schema::create('school_websites', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->unique(); $t->string('domain')->nullable()->unique();
            $t->string('template')->default('classic'); $t->json('theme')->nullable(); $t->json('pages')->nullable();
            $t->json('content')->nullable(); $t->boolean('status')->default(false); $t->timestamps();
        });

        DB::table('organizations')->insert([
            ['id' => 1, 'name' => ' The Demo School ', 'education_board' => 'CBSE', 'mobile_number' => '9457293157', 'address' => 'Agra, UP', 'email' => null],
            ['id' => 2, 'name' => 'Another School', 'education_board' => 'CBSE', 'mobile_number' => null, 'address' => null, 'email' => null],
        ]);
        // The school wrote its vision itself; the rest of the profile is empty.
        DB::table('school_infos')->insert(['organization_id' => 1, 'usm_vision' => 'Our own vision.']);
        // Created quietly: a site created live tells the super admins.
        SchoolWebsite::withoutEvents(function () {
            SchoolWebsite::create(['organization_id' => 1, 'domain' => 'demo.test', 'status' => true, 'pages' => ['home', 'about', 'contact'],
                'content' => ['motto' => 'Our own motto', 'pages' => ['labs' => ['heading' => 'Our Labs', 'body' => 'Typed by the school.']]]]);
            SchoolWebsite::create(['organization_id' => 2, 'domain' => 'other.test', 'status' => true, 'pages' => ['home']]);
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function site(int $org = 1): SchoolWebsite
    {
        $site = SchoolWebsite::where('organization_id', $org)->first();
        $site->setRelation('organization', Organization::with('schoolInfo')->find($org));

        return $site;
    }

    private function render(string $path): string
    {
        $res = app(SchoolSiteController::class)->render($this->site(), $path);
        $this->assertSame(200, $res->getStatusCode(), $path);

        return html_entity_decode(preg_replace('/\s+/', ' ', strip_tags(preg_replace('#<(style|script)\b.*?</\1>#s', '', $res->getContent()))));
    }

    public function test_it_fills_the_demo_schools_whole_site_and_keeps_what_the_school_wrote(): void
    {
        $this->artisan('lms:seed-demo-website', ['--demo' => true])->assertSuccessful();

        $site = $this->site();
        $c    = $site->resolvedContent();

        // Every page switched on; the school's own words kept.
        $this->assertSame(array_keys(SchoolWebsite::allPages()), $site->pages);
        $this->assertSame('Our own motto', $c['motto']);
        $this->assertSame('Typed by the school.', $site->pageContent('labs')['body']);
        $this->assertSame('Our own vision.', $c['vision']);

        // The rest filled: slider, lists, desks and text pages.
        $this->assertCount(5, $c['hero_slides']);
        $this->assertCount(9, $c['facilities']);
        $this->assertCount(18, $c['gallery']);
        $this->assertCount(10, $c['results']);
        $this->assertStringContainsString('Dr. Ramesh Chandra Sharma', $site->pageContent('chairman')['body']);
        $this->assertNotSame('', $site->pageContent('principal-desk')['image']);
        $this->assertCount(6, $site->pageContent('sports')['photos']);

        // The School Profile's empty texts and team, the rules, routes and calendar.
        $info = DB::table('school_infos')->where('organization_id', 1)->first();
        $this->assertSame('Our own vision.', $info->usm_vision);
        $this->assertStringContainsString('The Demo School is a co-educational', $info->about_school);
        $this->assertSame(9, DB::table('school_management_teams')->count());
        $this->assertSame(6, DB::table('transportations')->where('organization_id', 1)->count());
        $this->assertSame(34, DB::table('time_tables')->where('organization_id', 1)->count());
        $rules = json_decode(DB::table('rules_and_regulations')->where('organization_id', 1)->value('content'), true);
        $this->assertNotEmpty($rules['sections']);
        $this->assertCount(5, $rules['additional_info']);

        // A sample PDF for every document, stored with the website's uploads.
        $docs = $site->pageDocuments('disclosures');
        $this->assertCount(18, $docs);
        $this->assertCount(18, array_filter(array_column($docs, 'file')));
        $this->assertCount(22, Storage::disk('s3')->allFiles('admin/website/1/demo'));
        $this->assertStringStartsWith('%PDF', Storage::disk('s3')->get(Storage::disk('s3')->allFiles('admin/website/1/demo')[0]));

        // The other school is untouched.
        $this->assertSame(['home'], $this->site(2)->pages);
        $this->assertNull($this->site(2)->content);
        $this->assertSame(0, DB::table('transportations')->where('organization_id', 2)->count());
        $this->assertSame(0, DB::table('time_tables')->where('organization_id', 2)->count());
    }

    public function test_every_page_of_the_site_shows_its_data(): void
    {
        $this->artisan('lms:seed-demo-website', ['--demo' => true])->assertSuccessful();

        foreach (array_keys(SchoolWebsite::allPages()) as $slug) {
            $text = $this->render($slug);
            $this->assertStringNotContainsString('being updated', $text, $slug);
        }

        $this->assertStringContainsString('Admissions Open', $this->render('home'));
        $this->assertStringContainsString('Our History', $this->render('about'));
        $this->assertStringContainsString("Chairman's Message", $this->render('chairman'));
        $this->assertStringContainsString('Mrs. Neha Kapoor', $this->render('team'));
        $this->assertStringContainsString('Route 1 – Civil Lines', $this->render('transport'));
        $this->assertStringContainsString('Annual Sports Meet', $this->render('academic-calendar'));
        $this->assertStringContainsString('School Timings', $this->render('rules'));
        $this->assertStringContainsString('Integrity', $this->render('values-goals'));
        $this->assertStringContainsString('2026 – Class XII', $this->render('result'));
        $this->assertStringContainsString('Copy of Affiliation', $this->render('disclosures'));
        $this->assertStringContainsString('Click to Download', $this->render('doc/1'));
    }

    public function test_a_second_run_adds_nothing_twice(): void
    {
        $this->artisan('lms:seed-demo-website', ['--demo' => true])->assertSuccessful();
        $content = $this->site()->content;

        $this->artisan('lms:seed-demo-website', ['--demo' => true])->assertSuccessful();

        $this->assertSame($content, $this->site()->content);
        $this->assertSame(9, DB::table('school_management_teams')->count());
        $this->assertSame(6, DB::table('transportations')->count());
        $this->assertSame(34, DB::table('time_tables')->count());
        $this->assertCount(22, Storage::disk('s3')->allFiles('admin/website/1/demo'));
    }

    public function test_the_migration_seeds_the_demo_school_on_deploy(): void
    {
        (require database_path('migrations/2026_10_21_200000_seed_demo_school_website.php'))->up();

        $this->assertCount(5, $this->site()->resolvedContent()['hero_slides']);
        $this->assertNull($this->site(2)->content);
    }

    public function test_it_needs_a_school(): void
    {
        $this->artisan('lms:seed-demo-website')->assertFailed();
        $this->artisan('lms:seed-demo-website', ['--org' => 99])->expectsOutputToContain('not found')->assertSuccessful();
    }
}
