<?php

namespace Tests\Feature;

use App\Livewire\Components\GeminiAssistant;
use App\Services\Gemini\GeminiClient;
use App\Services\Gemini\GeminiException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Assist answering slowly and its cross not working while it worked
 * (user's report, 2026-10-13): the configured model was swamped — "high
 * demand" 503s, or a connection that answered nothing for 45 s, retried for 45
 * more, past nginx's 60 s. A question now moves on to the next model, a swamped
 * one is set aside, the whole question has a deadline, and the panel shows and
 * hides in the browser.
 */
class SuperAssistFallbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.key'                 => 'base64:' . base64_encode(str_repeat('a', 32)),
            'cache.default'           => 'array',
            'gemini.api_key'          => 'test-key',
            'gemini.model'            => 'main-model',
            'gemini.fallback_models'  => 'second-model, third-model',
            'gemini.attempt_timeout'  => 20,
            'gemini.timeout'          => 45,
        ]);
        Cache::flush();
        Http::swap(new Factory());
    }

    private function answer(string $text): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => $text]]]]]];
    }

    private function urlModel($request): string
    {
        preg_match('#models/([^:]+):generateContent#', $request->url(), $m);

        return $m[1] ?? '?';
    }

    public function test_a_busy_model_hands_the_question_to_the_next_and_is_set_aside(): void
    {
        $asked = [];
        Http::fake(function ($request) use (&$asked) {
            $asked[] = $model = $this->urlModel($request);

            return $model === 'main-model'
                ? Http::response(['error' => ['message' => 'This model is currently experiencing high demand.']], 503)
                : Http::response($this->answer('from ' . $model), 200);
        });

        $client = new GeminiClient();
        $res = $client->generateContent(['contents' => []]);

        $this->assertSame('from second-model', data_get($res, 'candidates.0.content.parts.0.text'));
        $this->assertSame('second-model', $client->lastModel());
        $this->assertSame(['main-model', 'second-model'], $asked, 'no second try of the busy model');

        // The next call skips the busy one.
        $asked = [];
        $client->generateContent(['contents' => []]);
        $this->assertSame(['second-model'], $asked);
    }

    public function test_a_model_that_never_answers_is_left_after_one_wait(): void
    {
        $asked = [];
        Http::fake(function ($request) use (&$asked) {
            $asked[] = $model = $this->urlModel($request);

            return $model === 'main-model'
                ? Http::failedConnection('cURL error 28: Operation timed out after 20000 milliseconds with 0 bytes received')
                : Http::response($this->answer('ok'), 200);
        });

        $res = (new GeminiClient())->generateContent(['contents' => []]);

        $this->assertSame('ok', data_get($res, 'candidates.0.content.parts.0.text'));
        $this->assertSame(['main-model', 'second-model'], $asked);
    }

    public function test_every_model_busy_ends_in_a_user_message_not_a_crash(): void
    {
        Http::fake(fn () => Http::failedConnection('cURL error 28: Operation timed out'));

        try {
            (new GeminiClient())->generateContent(['contents' => []]);
            $this->fail('expected a GeminiException');
        } catch (GeminiException $e) {
            $this->assertSame(0, $e->status);
            $this->assertSame('The assistant could not be reached just now. Please try again.', $e->userMessage());
        }
    }

    public function test_a_question_past_its_deadline_is_turned_away(): void
    {
        Http::fake(fn () => Http::response($this->answer('late'), 200));

        try {
            (new GeminiClient())->generateContent(['contents' => []], microtime(true) + 1);
            $this->fail('expected a GeminiException');
        } catch (GeminiException $e) {
            $this->assertSame(503, $e->status);
            $this->assertStringContainsString('very busy', $e->userMessage());
        }
        Http::assertNothingSent();
    }

    public function test_a_request_naming_a_cache_stays_on_the_main_model(): void
    {
        $asked = [];
        Http::fake(function ($request) use (&$asked) {
            $asked[] = $this->urlModel($request);

            return Http::response(['error' => ['message' => 'busy']], 503);
        });

        try {
            (new GeminiClient())->generateContent(['contents' => [], 'cachedContent' => 'cachedContents/abc']);
        } catch (GeminiException $e) {
            $this->assertSame(503, $e->status);
        }
        // The main model only (Laravel's own retry on a 5xx, as before).
        $this->assertSame(['main-model'], array_values(array_unique($asked)));
    }

    public function test_bad_requests_are_not_passed_along(): void
    {
        $asked = [];
        Http::fake(function ($request) use (&$asked) {
            $asked[] = $this->urlModel($request);

            return Http::response(['error' => ['message' => 'API key not valid']], 403);
        });

        $this->expectException(GeminiException::class);
        try {
            (new GeminiClient())->generateContent(['contents' => []]);
        } finally {
            $this->assertSame(['main-model'], $asked);
        }
    }

    public function test_no_fallbacks_configured_keeps_the_one_model(): void
    {
        config(['gemini.fallback_models' => '']);
        $client = new GeminiClient();

        $this->assertSame(['main-model'], $client->models());
    }

    public function test_the_panel_opens_and_closes_in_the_browser(): void
    {
        $user = new \App\Models\User(['name' => 'Super']);
        $user->id = 1;
        $user->role = 'super-admin';
        $this->actingAs($user);

        $page = Livewire::test(GeminiAssistant::class);
        $html = $page->html();

        // Always in the page, hidden until shown; the cross, the backdrop and
        // Esc hide it without a request first.
        $this->assertStringContainsString('x-show="shown" style="display: none"', $html);
        $this->assertStringContainsString('x-on:click="hide()" title="Close"', $html);
        $this->assertStringContainsString('x-on:keydown.escape.window="if (shown) hide()"', $html);
        $this->assertStringContainsString('x-on:gemini-toggle.window="shown ? hide() : show()"', $html);

        $page->call('opened')->assertSet('open', true)->call('close')->assertSet('open', false);
    }
}
