<?php

namespace Tests\Feature;

use App\Support\Platform;
use App\Support\WebsiteDesign;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The public website comes in two complete looks (2026-11-18): the new design
 * (components/site, the default) and the classic one (components/website,
 * untouched). ?design=classic|new previews the other one in that browser only;
 * the super admin's Profile → Website Data switch picks it for every visitor.
 */
class WebsiteDesignTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('a', 32))]);

        Schema::create('platform_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });
        Schema::create('website_pages', function (Blueprint $t) {
            $t->id();
            $t->string('slug');
            $t->json('metadata')->nullable();
            $t->date('last_updated')->nullable();
            $t->timestamps();
        });
        Schema::create('faqs', function (Blueprint $t) {
            $t->id();
            $t->string('category')->nullable();
            $t->string('question');
            $t->text('answer');
            $t->timestamps();
        });
        Schema::create('blogs', function (Blueprint $t) {
            $t->id();
            $t->string('slug');
            $t->string('cover_image')->nullable();
            $t->string('category')->nullable();
            $t->string('title');
            $t->string('heading')->nullable();
            $t->text('description')->nullable();
            $t->json('paragraphs')->nullable();
            $t->unsignedInteger('views')->default(0);
            $t->timestamps();
        });

        Platform::set(WebsiteDesign::SETTING_KEY, null); // fresh static cache per test
    }

    public function test_every_page_renders_in_the_new_design_by_default(): void
    {
        $pages = ['/', '/web/about', '/web/features', '/web/pricing', '/web/contact', '/web/demo',
            '/web/privacy', '/web/refund-policy', '/web/terms-conditions', '/web/terms-of-use',
            '/web/why-us', '/web/services', '/web/careers', '/web/become-an-executive',
            '/web/blogs', '/web/faqs'];

        foreach ($pages as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('site/site.css', false)
                ->assertDontSee('class="navbar"', false);
        }
    }

    public function test_the_text_stays_the_same_in_the_new_design(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Learning Management System.')
            ->assertSee('How It')
            ->assertSee('Partnered Schools')
            ->assertSee('Frequently Asked')
            ->assertSee('Ready to Transform Your');

        $this->get('/web/pricing')
            ->assertOk()
            ->assertSee('per month/user')
            ->assertSee('The School Pays')
            ->assertSee('Everything Included in');
    }

    public function test_a_browser_can_preview_the_classic_design_and_go_back(): void
    {
        $classic = $this->get('/?design=classic');
        $classic->assertOk()
            ->assertSee('hero-section', false)
            ->assertDontSee('site/site.css', false)
            ->assertCookie('site_design');

        $this->get('/web/about?design=new')->assertOk()->assertSee('site/site.css', false);
    }

    public function test_the_super_admin_switch_serves_the_classic_design_to_everyone(): void
    {
        Platform::set(WebsiteDesign::SETTING_KEY, WebsiteDesign::CLASSIC);

        $this->get('/')->assertOk()->assertSee('hero-section', false)->assertDontSee('site/site.css', false);
        $this->get('/web/pricing')->assertOk()->assertDontSee('site/site.css', false);

        Platform::set(WebsiteDesign::SETTING_KEY, WebsiteDesign::NEW);

        $this->get('/')->assertOk()->assertSee('site/site.css', false);
    }

    public function test_the_careers_page_lists_the_admin_managed_jobs(): void
    {
        \App\Models\WebsitePage::create(['slug' => 'careers', 'metadata' => ['jobs' => [
            ['role' => 'Business Development Executive', 'salary' => '₹3–6 LPA', 'location' => 'Remote'],
        ]]]);

        $this->get('/web/careers')
            ->assertOk()
            ->assertSee('Business Development Executive')
            ->assertSee('₹3–6 LPA');
    }
}
