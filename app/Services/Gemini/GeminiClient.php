<?php

namespace App\Services\Gemini;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin HTTP layer over the Generative Language API (AI Studio keys).
 *
 * Only the two endpoints the assistant needs: `:generateContent` and the
 * explicit context cache (`cachedContents`). The key travels in the
 * `x-goog-api-key` header rather than the query string so it never lands in an
 * access log or an exception trace.
 */
class GeminiClient
{
    public function configured(): bool
    {
        return filled($this->key());
    }

    public function model(): string
    {
        return (string) config('gemini.model', 'gemini-2.5-flash');
    }

    /** Fully-qualified model name, the form the cache API expects. */
    public function modelPath(): string
    {
        $model = $this->model();

        return str_starts_with($model, 'models/') ? $model : 'models/' . $model;
    }

    private function key(): ?string
    {
        return config('gemini.api_key');
    }

    private function base(): string
    {
        return rtrim((string) config('gemini.base_url'), '/');
    }

    private function http(): PendingRequest
    {
        return Http::withHeaders([
            'x-goog-api-key' => (string) $this->key(),
            'Content-Type'   => 'application/json',
        ])
            ->timeout((int) config('gemini.timeout', 45))
            // Retry a dropped connection or a server-side blip once. Never a
            // 4xx: a 429 on the free tier means "wait ~a minute", so retrying
            // it immediately just spends another request against the quota,
            // and a 400/403/404 will fail identically every time.
            ->retry(2, 600, function ($exception, $request) {
                if ($exception instanceof ConnectionException) {
                    return true;
                }

                return $exception instanceof RequestException
                    && $exception->response->serverError();
            }, throw: false);
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     *
     * @throws GeminiException
     */
    public function generateContent(array $payload): array
    {
        $res = $this->http()->post($this->base() . '/' . $this->modelPath() . ':generateContent', $payload);

        if ($res->failed()) {
            $body = $res->json();
            Log::warning('gemini.generateContent failed', [
                'status' => $res->status(),
                'error'  => data_get($body, 'error.message'),
            ]);

            throw new GeminiException(
                (string) (data_get($body, 'error.message') ?: 'generateContent failed'),
                $res->status(),
                is_array($body) ? $body : null,
            );
        }

        return (array) ($res->json() ?? []);
    }

    /**
     * Upload a reusable context block. Returns the cache resource name
     * (`cachedContents/…`), or null when this tier will not accept one — the
     * caller falls back to sending the context inline.
     *
     * @param  array<string,mixed>  $payload
     */
    public function createCachedContent(array $payload): ?string
    {
        $res = $this->http()->post($this->base() . '/cachedContents', $payload);

        if ($res->failed()) {
            Log::info('gemini.cachedContents unavailable, falling back to inline context', [
                'status' => $res->status(),
                'error'  => data_get($res->json(), 'error.message'),
            ]);

            return null;
        }

        $name = data_get($res->json(), 'name');

        return is_string($name) && $name !== '' ? $name : null;
    }

    public function deleteCachedContent(string $name): void
    {
        $this->http()->delete($this->base() . '/' . ltrim($name, '/'));
    }
}
