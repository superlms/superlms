<?php

namespace Tests\Feature;

use App\Support\Platform;
use App\Support\WebsiteDesign;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * superlms.in's hero (2026-11-19): the main features swipe by, each on a
 * school photo (public/site/hero/<name>.webp) with its name, a line and a
 * small live note, a pill for each below. Until every photo is in place the
 * hero keeps the product mockup it had.
 */
class HomeHeroSliderTest extends TestCase
{
    private const PHOTOS = ['attendance', 'fees', 'learning', 'exams', 'alerts'];

    /** Photos this test put in place (only those are taken away again). */
    private array $made = [];

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
        Platform::set(WebsiteDesign::SETTING_KEY, null);
    }

    protected function tearDown(): void
    {
        foreach ($this->made as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    private function allPhotosThere(): bool
    {
        foreach (self::PHOTOS as $name) {
            if (!is_file(public_path("site/hero/$name.webp"))) {
                return false;
            }
        }

        return true;
    }

    public function test_the_hero_keeps_its_mockup_until_every_photo_is_in_place(): void
    {
        if ($this->allPhotosThere()) {
            $this->markTestSkipped('The photos are in place.');
        }

        $this->get('/')->assertOk()
            ->assertSee('class="mk hero-mk"', false)
            ->assertDontSee('data-hero-slider', false);
    }

    public function test_with_the_photos_the_main_features_swipe_by(): void
    {
        @mkdir(public_path('site/hero'), 0777, true);
        foreach (self::PHOTOS as $name) {
            $file = public_path("site/hero/$name.webp");
            if (!is_file($file)) {
                file_put_contents($file, 'x');
                $this->made[] = $file;
            }
        }

        $html = $this->get('/')->assertOk()
            ->assertSee('data-hero-slider', false)
            ->assertDontSee('class="mk hero-mk"', false)
            ->getContent();

        // One slide, one bar and one pill a feature, in order, the first one on.
        $this->assertSame(5, substr_count($html, 'class="hs-slide '));
        $this->assertSame(5, substr_count($html, '<i><b></b></i>'));
        foreach (self::PHOTOS as $name) {
            $this->assertStringContainsString('site/hero/' . $name . '.webp', $html);
        }
        $this->assertMatchesRegularExpression('/aria-selected="true" class="is-on" data-hs-go="0">.*?Attendance/s', $html);
        foreach (['Attendance in seconds', 'Fees, collected online', 'Learning, anytime', 'Exams &amp; report cards', 'Parents always in the loop'] as $title) {
            $this->assertStringContainsString('<h3>' . $title . '</h3>', $html);
        }
        // The first photo comes at once; the others when needed.
        $this->assertMatchesRegularExpression('/attendance\.webp"[^>]*loading="eager" fetchpriority="high"/', $html);
        $this->assertMatchesRegularExpression('/fees\.webp"[^>]*loading="lazy"/', $html);
        // The page's text stays as it was.
        $this->assertStringContainsString('Learning Management System.', $html);
    }
}
