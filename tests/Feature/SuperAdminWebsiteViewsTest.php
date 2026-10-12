<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Blogs;
use App\Livewire\SuperAdmin\Faqs;
use App\Livewire\SuperAdmin\QuickLinks;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin FAQs and Blogs: no description under the header, a View that
 * opens the item in a slide-in (FAQ in the Enquiry view's look, the blog as
 * it reads), the blog card's actions as the Students list's icons; Quick
 * Links laid out 6 × 4.
 */
class SuperAdminWebsiteViewsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('w', 32))]);
        Carbon::setTestNow('2026-10-12 11:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function text(string $html): string
    {
        return preg_replace('/\s+/', ' ', strip_tags($html));
    }

    public function test_a_faq_opens_in_a_view_slide_in(): void
    {
        Schema::create('faqs', function (Blueprint $t) {
            $t->id();
            $t->string('category')->default('General');
            $t->string('question');
            $t->text('answer')->nullable();
            $t->timestamps();
        });
        DB::table('faqs')->insert([
            'category' => 'Payments', 'question' => 'Can parents pay fees online?', 'answer' => "Yes, from the parent app.\nUPI and cards work.",
            'created_at' => '2026-10-01 09:30:00', 'updated_at' => '2026-10-05 12:00:00',
        ]);

        $page = Livewire::test(Faqs::class);
        $html = $page->html();
        $this->assertStringNotContainsString('Manage the questions shown on the website FAQ page.', $html);
        $this->assertStringContainsString('wire:click="openView(1)" title="View"', $html);
        $this->assertStringContainsString('wire:click="openEdit(1)"', $html);
        $this->assertStringContainsString('wire:click="confirmDelete(1)"', $html);

        $view = $this->text($page->call('openView', 1)->html());
        foreach (['Can parents pay fees online? FAQ · Payments', 'Category Payments', 'Question Can parents pay fees online?',
                  'Answer Yes, from the parent app. UPI and cards work.', 'Added on 01 Oct 2026 · 9:30 AM',
                  'Updated on 05 Oct 2026 · 12:00 PM', 'Edit Close'] as $seen) {
            $this->assertStringContainsString($seen, $view);
        }

        // Edit from the view: the view closes, the form opens on the FAQ.
        $page->call('editFromView')
            ->assertSet('viewId', null)
            ->assertSet('showPanel', true)
            ->assertSet('editId', 1)
            ->assertSet('question', 'Can parents pay fees online?');

        $page->call('closePanel')->call('openView', 1)->call('closeView')->assertSet('viewId', null);
        $page->call('openView', 99)->assertSet('viewId', null);
    }

    public function test_a_blog_opens_in_a_view_slide_in_and_its_card_has_action_icons(): void
    {
        Schema::create('blogs', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('cover_image')->nullable();
            $t->string('category')->nullable();
            $t->string('title');
            $t->string('heading')->nullable();
            $t->longText('description')->nullable();
            $t->json('paragraphs')->nullable();
            $t->unsignedBigInteger('views')->default(0);
            $t->timestamps();
        });
        DB::table('blogs')->insert([
            'slug' => 'going-paperless', 'category' => 'Guides', 'title' => 'Going paperless',
            'heading' => 'How schools save time', 'views' => 42,
            'paragraphs' => json_encode(['First step: move attendance online.', 'Read [our guide](https://superlms.in/guide) <b>now</b>.']),
            'created_at' => '2026-10-02 08:15:00', 'updated_at' => '2026-10-02 08:15:00',
        ]);

        $page = Livewire::test(Blogs::class);
        $html = $page->html();
        $this->assertStringNotContainsString('Write and manage the articles shown on the website.', $html);
        foreach (['wire:click="openView(1)" title="View"', 'wire:click="openEdit(1)" title="Edit"', 'wire:click="confirmDelete(1)" title="Delete"'] as $action) {
            $this->assertStringContainsString($action, $html);
        }
        // Icons, no worded buttons.
        $this->assertStringNotContainsString('>Edit</button>', $html);
        $this->assertStringNotContainsString('>Delete</button>', $html);

        $viewHtml = $page->call('openView', 1)->html();
        $view     = $this->text($viewHtml);
        foreach (['Going paperless Blog · Guides · 02 Oct 2026 · 42 views', 'How schools save time',
                  'First step: move attendance online.', 'Posted on 02 Oct 2026 · 8:15 AM', 'Views 42', 'Edit Close'] as $seen) {
            $this->assertStringContainsString($seen, $view);
        }
        // Links work as on the website; any HTML in the text stays text.
        $this->assertStringContainsString('<a href="https://superlms.in/guide" target="_blank" rel="noopener noreferrer"', $viewHtml);
        $this->assertStringContainsString('&lt;b&gt;now&lt;/b&gt;', $viewHtml);

        $page->call('editFromView')
            ->assertSet('viewId', null)
            ->assertSet('showPanel', true)
            ->assertSet('editId', 1)
            ->assertSet('title', 'Going paperless');
    }

    public function test_quick_links_are_a_six_by_four_grid(): void
    {
        $user = new User(['name' => 'Super', 'email' => 'super@example.com']);
        $user->id = 1;
        $user->role = 'super-admin';
        $this->actingAs($user);

        $page = Livewire::test(QuickLinks::class);

        $this->assertCount(24, $page->viewData('orderedLinks'));
        $this->assertStringContainsString('md:grid-cols-6 md:grid-rows-4 md:auto-rows-fr', $page->html());
    }
}
