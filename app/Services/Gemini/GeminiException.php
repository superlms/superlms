<?php

namespace App\Services\Gemini;

use RuntimeException;

/**
 * An assistant API call that failed in a way the user should hear about.
 * `userMessage()` is safe to show in the chat panel — it never leaks the key,
 * the endpoint, the raw upstream payload, or the name of the model behind the
 * assistant. To the user this is Super LMS; who answers for it is not their
 * concern, and naming a third party in an error only sends them to the wrong
 * support desk.
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
            $this->status === 429 => 'The assistant has hit its own service limit for the moment. ' . $this->retryHint(),
            $this->status === 401,
            $this->status === 403 => 'The API key behind the assistant was rejected. An administrator needs to check it in the environment settings.',
            $this->status === 400 => 'The assistant could not process that request. Try rephrasing the question.',
            $this->status >= 500  => 'The assistant is temporarily unavailable. Please try again shortly.',
            default               => 'The assistant could not be reached just now. Please try again.',
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
