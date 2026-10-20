<?php

namespace Tests\Feature;

use App\Http\Middleware\DownloadReady;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The panels' download loader follows a download link with `_dl=<token>`;
 * the answer hands the token back in a cookie the page's script can read —
 * plain (not encrypted) and not HttpOnly — whatever the answer is.
 */
class DownloadReadyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:' . base64_encode(str_repeat('k', 32))]);
        Route::middleware('web')->get('/_test/file', fn () => response()->streamDownload(fn () => print('x'), 'a.txt'));
        Route::middleware('web')->get('/_test/broken', fn () => abort(500));
    }

    public function test_a_download_followed_with_a_token_hands_it_back_in_a_readable_cookie(): void
    {
        $response = $this->get('/_test/file?_dl=Ab12cd34Ef56gh78');

        $cookie = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === DownloadReady::COOKIE);
        $this->assertNotNull($cookie);
        $this->assertSame('Ab12cd34Ef56gh78', $cookie->getValue());
        $this->assertFalse($cookie->isHttpOnly());
        $this->assertSame('/', $cookie->getPath());
    }

    public function test_an_error_answer_hands_the_token_back_too(): void
    {
        $response = $this->get('/_test/broken?_dl=Ab12cd34Ef56gh78');

        $response->assertStatus(500);
        $this->assertTrue(collect($response->headers->getCookies())->contains(fn ($c) => $c->getName() === DownloadReady::COOKIE));
    }

    public function test_no_token_or_a_malformed_one_sets_nothing(): void
    {
        foreach (['/_test/file', '/_test/file?_dl=<script>', '/_test/file?_dl=short'] as $url) {
            $names = collect($this->get($url)->headers->getCookies())->map->getName();
            $this->assertNotContains(DownloadReady::COOKIE, $names, $url);
        }
    }
}
