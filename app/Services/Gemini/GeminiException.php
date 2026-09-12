<?php

namespace App\Services\Gemini;

use RuntimeException;

/**
 * A Gemini API call that failed in a way the user should hear about.
 * `userMessage()` is safe to show in the chat panel — it never leaks the key,
 * the endpoint or the raw upstream payload.
 */
class GeminiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly ?array $body = null,
        /** Set when we generated the failure ourselves and the wording is already user-ready. */
        public readonly ?string $friendly = null,
    ) {
        parent::__construct($message);
    }

    public function userMessage(): string
    {
        if ($this->friendly !== null) {
            return $this->friendly;
        }

        return match (true) {
            $this->status === 429 => 'The Gemini quota is used up for now. ' . $this->retryHint(),
            $this->status === 401,
            $this->status === 403 => 'Gemini rejected the API key. Please check the GEMINI_API_KEY setting.',
            $this->status === 400 => 'Gemini could not process that request. Try rephrasing the question.',
            $this->status >= 500  => 'Gemini is temporarily unavailable. Please try again shortly.',
            default               => 'Could not reach Gemini just now. Please try again.',
        };
    }

    /**
     * A quota error carries a RetryInfo detail saying how long to wait. Quoting
     * it beats "try again later" — the free tier's window is short enough that
     * the real number is usually "under a minute".
     */
    private function retryHint(): string
    {
        foreach ((array) data_get($this->body, 'error.details', []) as $detail) {
            $delay = data_get($detail, 'retryDelay');

            if (is_string($delay) && preg_match('/^(\d+(?:\.\d+)?)s$/', $delay, $m)) {
                return 'Try again in about ' . max(1, (int) ceil((float) $m[1])) . ' seconds.';
            }
        }

        return 'Please try again in a minute.';
    }
}
