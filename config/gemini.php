<?php

return [

    /*
    |--------------------------------------------------------------------------
    | The Super LMS assistant
    |--------------------------------------------------------------------------
    |
    | The assistant is a read-only bridge over this LMS: a per-organization
    | knowledge pack plus a set of scoped query tools. It never sees data
    | outside the signed-in user's organization, it is held to that login's own
    | screen permissions, and it is instructed to refuse anything that is not
    | about this LMS.
    |
    | It is called "Super LMS" everywhere a user can see. The `gemini.*` keys
    | and the GEMINI_* environment variables keep their names on purpose: they
    | are what the production secret and the deployed .env already carry, and
    | renaming them would take the assistant offline until every box was
    | edited. The model behind it is never named to a user.
    |
    | The API key is NEVER committed. Set GEMINI_API_KEY in the environment
    | (locally in .env, in production in the superlms/app secret).
    |
    */

    // What the assistant calls itself in the panel. Never the model's name.
    'brand' => env('LMS_ASSISTANT_NAME', 'Super LMS'),

    'api_key'  => env('GEMINI_API_KEY'),

    'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),

    // Free-tier model on AI Studio.
    //
    // NOTE: the gemini-2.5-* ids are closed to keys issued from late 2026 on —
    // generateContent answers 404 "no longer available to new users". So the
    // default is the moving alias `gemini-flash-latest`, which always points at
    // the current free-tier Flash and will not break again the next time Google
    // rotates a version. Pin an exact id with GEMINI_MODEL if you need one.
    // flash-lite: the cheapest free-tier model that still does function calling,
    // and the one with the roomiest free request quota. `gemini-flash-latest`
    // resolves to full Flash, whose free allowance runs out far sooner.
    'model'    => env('GEMINI_MODEL', 'gemini-flash-lite-latest'),

    'enabled'  => env('GEMINI_ENABLED', true),

    'timeout'  => (int) env('GEMINI_TIMEOUT', 45),

    'generation' => [
        // Low temperature: this is a reporting assistant, not a writer.
        'temperature'      => (float) env('GEMINI_TEMPERATURE', 0.2),
        'maxOutputTokens'  => (int) env('GEMINI_MAX_OUTPUT_TOKENS', 1400),
        // Thinking budget. Left unset by default: 2.5/3.x Flash accepts
        // thinkingBudget, but some siblings (flash-lite among them) answer 400
        // "Request contains an invalid argument" for it. Set
        // GEMINI_THINKING_BUDGET=0 to disable reasoning on a model that does
        // take it — the request retries once without the field if it is
        // rejected, so a wrong setting degrades rather than breaks.
        'thinkingBudget'   => env('GEMINI_THINKING_BUDGET') !== null
            ? (int) env('GEMINI_THINKING_BUDGET')
            : null,
    ],

    'cache' => [
        // Explicit context caching (POST /cachedContents). The knowledge pack +
        // system instruction + tool declarations are uploaded once and reused,
        // so every question only pays for the question itself.
        'enabled' => env('GEMINI_CACHE_ENABLED', true),

        // How long Gemini keeps the uploaded context.
        'ttl_seconds' => (int) env('GEMINI_CACHE_TTL', 3600),

        // How long we keep the generated LMS snapshot before rebuilding it.
        // Short enough that "how many students" stays current, long enough that
        // a burst of questions does not re-run the whole summary query set.
        'snapshot_seconds' => (int) env('GEMINI_SNAPSHOT_TTL', 300),

        // Some tiers (free included, at times) reject cachedContents outright.
        // When that happens we stop retrying for this long and fall back to
        // sending the context inline, where implicit caching still applies.
        'unsupported_backoff_seconds' => 6 * 3600,
    ],

    // Burst guard, per signed-in user. Stops one tab hammering the API; the
    // real budget is the daily allowance below.
    'rate_limit' => [
        'per_minute' => (int) env('GEMINI_RATE_PER_MINUTE', 12),
    ],

    // The daily allowance, per ROLE, resetting at midnight in the app timezone.
    //
    // A bucket is one role inside one school: every admin of a school draws
    // from that school's admin allowance, every sub-admin from its sub-admin
    // allowance, and so on — so a school with ten sub-admins cannot spend ten
    // times the budget. 0 means unlimited, which is what the platform
    // super-admin gets.
    'quota' => [
        'per_role_per_day' => [
            'super-admin'     => (int) env('GEMINI_LIMIT_SUPER_ADMIN', 0),
            'sub-super-admin' => (int) env('GEMINI_LIMIT_SUB_SUPER_ADMIN', 100),
            'admin'           => (int) env('GEMINI_LIMIT_ADMIN', 100),
            'sub-admin'       => (int) env('GEMINI_LIMIT_SUB_ADMIN', 50),
            'accounts'        => (int) env('GEMINI_LIMIT_ACCOUNTS', 50),
        ],

        // Anything not named above.
        'default_per_day' => (int) env('GEMINI_LIMIT_DEFAULT', 50),
    ],

    // How many tool round-trips one question may take. A question like "every
    // driver with their routes" legitimately needs several — the guard is
    // against a model that loops, not against a thorough answer. When the cap
    // is reached the assistant spends one more call answering from what it has
    // already gathered rather than giving the question back.
    'max_tool_rounds' => (int) env('GEMINI_MAX_TOOL_ROUNDS', 8),

    // Turns of chat history sent back with each question.
    'history_turns' => 8,

    // Panels that get the floating assistant.
    'roles' => ['admin', 'sub-admin', 'accounts', 'super-admin', 'sub-super-admin'],

];
