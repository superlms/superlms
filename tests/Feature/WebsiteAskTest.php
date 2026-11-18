<?php

namespace Tests\Feature;

use App\Services\Gemini\WebsiteKnowledge;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The website's "Ask anything" pill (2026-11-18): POST /api/website/ask answers
 * a visitor from superlms.in's own text — every page of the new design (the
 * mockups' made-up names and numbers left out), the FAQs and blog posts from
 * the database — through the panels' model and key, within a per-visitor and
 * a site-wide daily allowance.
 */
class WebsiteAskTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.key'              => 'base64:' . base64_encode(str_repeat('a', 32)),
            'gemini.api_key'       => 'test-key',
            'gemini.enabled'       => true,
            'gemini.model'         => 'test-model',
            'gemini.fallback_models' => '',
        ]);

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

        DB::table('faqs')->insert(['category' => 'Billing', 'question' => 'Can we pay quarterly?', 'answer' => 'Plans are billed annually.', 'created_at' => now(), 'updated_at' => now()]);
        WebsiteKnowledge::forget();
    }

    private function fakeAnswer(string $text): void
    {
        Http::fake(['*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => $text]]]]]])]);
    }

    public function test_a_question_is_answered_from_the_website_text(): void
    {
        $this->fakeAnswer('The School Plan is **₹250** per student a year. See [Pricing](/web/pricing).');

        $this->postJson('/api/website/ask', [
            'question' => 'How much does it cost?',
            'history'  => [
                ['role' => 'user', 'text' => 'Hi'],
                ['role' => 'model', 'text' => 'Hello! Ask me about SUPERLMS.'],
            ],
        ])
            ->assertOk()
            ->assertJson(['success' => true, 'answer' => 'The School Plan is **₹250** per student a year. See [Pricing](/web/pricing).']);

        Http::assertSent(function (HttpRequest $r) {
            $instruction = (string) data_get($r->data(), 'systemInstruction.parts.0.text');
            $contents    = (array) data_get($r->data(), 'contents');

            return str_contains($r->url(), 'test-model:generateContent')
                && $r->hasHeader('x-goog-api-key', 'test-key')
                // the site's own words, from the pages and the database
                && str_contains($instruction, 'The School Pays')
                && str_contains($instruction, 'per month/user')
                && str_contains($instruction, 'Can we pay quarterly?')
                && str_contains($instruction, '/web/pricing')
                // not the mockups' made-up data
                && !str_contains($instruction, 'Riya Verma')
                && !str_contains($instruction, '₹12.4L')
                // earlier turns, then the question
                && count($contents) === 3
                && data_get($contents, '1.role') === 'model'
                && data_get($contents, '2.parts.0.text') === 'How much does it cost?';
        });
    }

    public function test_an_empty_or_too_long_question_is_refused(): void
    {
        Http::fake();

        $this->postJson('/api/website/ask', ['question' => ''])->assertStatus(422);
        $this->postJson('/api/website/ask', ['question' => str_repeat('a', 501)])->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_without_a_key_the_visitor_is_sent_to_the_team(): void
    {
        config(['gemini.api_key' => null]);
        Http::fake();

        $this->postJson('/api/website/ask', ['question' => 'Hello?'])
            ->assertStatus(503)
            ->assertJson(['success' => false])
            ->assertJsonFragment(['message' => 'The assistant is resting right now. Call or WhatsApp the SUPERLMS team on +91 9084748563 — they will be glad to help.']);

        Http::assertNothingSent();
    }

    public function test_each_visitor_has_a_daily_allowance_and_failures_do_not_count(): void
    {
        config(['website.ask.per_visitor_per_day' => 2]);

        $ok = ['candidates' => [['content' => ['parts' => [['text' => 'Yes.']]]]]];
        Http::fake(['*' => Http::sequence()
            ->push(['error' => ['message' => 'boom']], 500)
            ->push(['error' => ['message' => 'boom']], 500)
            ->push(['error' => ['message' => 'boom']], 500)
            ->push($ok)->push($ok)->push($ok)]);

        // A failed question (the client retries a 5xx twice) is given back.
        $this->postJson('/api/website/ask', ['question' => 'First?'])->assertStatus(503)->assertJson(['success' => false]);

        $this->postJson('/api/website/ask', ['question' => 'One?'])->assertOk();
        $this->postJson('/api/website/ask', ['question' => 'Two?'])->assertOk();

        $this->postJson('/api/website/ask', ['question' => 'Three?'])
            ->assertStatus(429)
            ->assertJson(['success' => false])
            ->assertJsonPath('message', "You've reached today's limit for questions here. For anything more, call or WhatsApp the SUPERLMS team on +91 9084748563, or book a free demo.");
    }

    public function test_the_pill_opens_the_chat_on_every_page(): void
    {
        $this->get('/web/about')
            ->assertOk()
            ->assertSee('id="askPanel"', false)
            ->assertSee('Super LMS Assistant')
            ->assertSee('How much does SUPERLMS cost?');
    }
}
