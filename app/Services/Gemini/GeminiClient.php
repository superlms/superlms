<?php

namespace App\Services\Gemini;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin HTTP layer over the Generative Language API (AI Studio keys).
 *
 * Only the two endpoints the assistant needs: `:generateContent` and the
 * explicit context cache (`cachedContents`). The key travels in the
 * `x-goog-api-key` header rather than the query string so it never lands in an
 * access log or an exception trace.
 *
 * When the configured model is swamped — "high demand" 503s, quota 429s, or a
 * connection that is made and then never answered — a question moves on to
 * the next model in `gemini.fallback_models` instead of waiting it out (that
 * wait was 45 s twice over, past the web server's own 60 s limit). A swamped
 * model is set aside for a couple of minutes so the next call goes straight
 * to one that answers.
 */
class GeminiClient
{
    /** The model that answered the last generateContent call. */
    private ?string $lastModel = null;

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
        return $this->pathFor($this->model());
    }

    /**
     * Every model a question may go to: the configured one first, then the
     * fallbacks, each once.
     *
     * @return array<int,string>
     */
    public function models(): array
    {
        $fallbacks = config('gemini.fallback_models', []);
        if (is_string($fallbacks)) {
            $fallbacks = explode(',', $fallbacks);
        }

        $all = array_map(fn ($m) => trim((string) $m), array_merge([$this->model()], (array) $fallbacks));

        return array_values(array_unique(array_filter($all, fn ($m) => $m !== '')));
    }

    public function lastModel(): ?string
    {
        return $this->lastModel;
    }

    private function pathFor(string $model): string
    {
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

    private function http(?int $timeout = null, bool $retry = true): PendingRequest
    {
        $request = Http::withHeaders([
            'x-goog-api-key' => (string) $this->key(),
            'Content-Type'   => 'application/json',
        ])
            ->timeout($timeout ?? (int) config('gemini.timeout', 45));

        // A model with another one behind it is not retried: the next model is
        // the retry, and a much quicker one than waiting on a swamped model.
        if (! $retry) {
            return $request;
        }

        // Retry a dropped connection or a server-side blip once. Never a
        // 4xx: a 429 on the free tier means "wait ~a minute", so retrying
        // it immediately just spends another request against the quota,
        // and a 400/403/404 will fail identically every time. Nor a call
        // that connected and then timed out waiting for the answer: that is
        // a swamped model, and waiting the whole timeout again only doubled
        // the wait.
        return $request->retry(2, 600, function ($exception, $request) {
            if ($exception instanceof ConnectionException) {
                return ! $this->timedOut($exception);
            }

            return $exception instanceof RequestException
                && $exception->response->serverError();
        }, throw: false);
    }

    /** cURL error 28: connected, then nothing came back in time. */
    private function timedOut(\Throwable $e): bool
    {
        return str_contains($e->getMessage(), 'cURL error 28') || stripos($e->getMessage(), 'timed out') !== false;
    }

    /**
     * Ask a model. A request that names an explicit cache goes to the model
     * that cache was made for, as it always did; any other request goes to the
     * configured model and, when that one is swamped or gone, to the next
     * fallback — set-aside models last. `$deadline` (a microtime) caps the
     * whole thing, so a question is answered or turned away before the web
     * server gives up on the request.
     *
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     *
     * @throws GeminiException
     */
    public function generateContent(array $payload, ?float $deadline = null): array
    {
        $models = isset($payload['cachedContent']) ? [$this->model()] : $this->orderedModels();
        $last   = count($models) - 1;

        foreach ($models as $i => $model) {
            $timeout = $i < $last
                ? min((int) config('gemini.attempt_timeout', 20), (int) config('gemini.timeout', 45))
                : (int) config('gemini.timeout', 45);

            if ($deadline !== null) {
                $left = (int) floor($deadline - microtime(true));

                if ($left < 3) {
                    throw new GeminiException('question deadline reached', 503, null,
                        'Super Assist is very busy right now and could not get an answer in time. Please ask again in a minute.');
                }

                $timeout = min($timeout, $left);
            }

            try {
                $response = $this->post($model, $payload, $timeout, $i === $last);
                $this->lastModel = $model;

                return $response;
            } catch (GeminiException $e) {
                if ($this->swamped($e)) {
                    $this->setAside($model, $e->status === 404 ? 6 * 3600 : (int) config('gemini.busy_rest_seconds', 120));
                }

                if ($i < $last && $this->swamped($e)) {
                    Log::info('gemini: model busy, trying the next one', ['model' => $model, 'status' => $e->status]);

                    continue;
                }

                throw $e;
            }
        }

        // Not reached: the last model either answers or throws.
        throw new GeminiException('no model to ask', 503);
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     *
     * @throws GeminiException
     */
    private function post(string $model, array $payload, int $timeout, bool $retry): array
    {
        try {
            $res = $this->http($timeout, $retry)->post($this->base() . '/' . $this->pathFor($model) . ':generateContent', $payload);
        } catch (ConnectionException $e) {
            Log::warning('gemini.generateContent unreachable', [
                'model'     => $model,
                'timed_out' => $this->timedOut($e),
            ]);

            // Status 0: "could not be reached" — the question is handed back
            // to the daily allowance and the user is told to try again.
            throw new GeminiException('connection failed', 0);
        }

        if ($res->failed()) {
            $body = $res->json();
            Log::warning('gemini.generateContent failed', [
                'model'  => $model,
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
     * Busy, out of its own quota, unreachable, or retired ("no longer
     * available") — another model may well answer. A 400 / 401 / 403 would
     * fail the same everywhere.
     */
    private function swamped(GeminiException $e): bool
    {
        return in_array($e->status, [0, 404, 408, 429], true) || $e->status >= 500;
    }

    /** The models in order, those set aside for being busy moved to the back. */
    private function orderedModels(): array
    {
        $models  = $this->models();
        $resting = array_values(array_filter($models, fn ($m) => Cache::has($this->restKey($m))));

        return array_values(array_merge(array_diff($models, $resting), $resting));
    }

    private function setAside(string $model, int $seconds): void
    {
        if (count($this->models()) > 1) {
            Cache::put($this->restKey($model), 1, max(10, $seconds));
        }
    }

    private function restKey(string $model): string
    {
        return 'gemini:busy:' . $model;
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
