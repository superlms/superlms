<?php

/*
|--------------------------------------------------------------------------
| Marketing website design
|--------------------------------------------------------------------------
| Which look the public website (/, /web/*) is served in:
|
|   'new'     — resources/views/components/site/*    (the current design)
|   'classic' — resources/views/components/website/* (the earlier design,
|               kept untouched so it can come back at any time)
|
| The super admin can switch it from Profile → Platform (stored in
| platform_settings as website_design), which wins over this default.
| Anyone can preview the other one in their own browser with ?design=classic
| or ?design=new (remembered in a cookie). See App\Support\WebsiteDesign.
*/

return [

    'design' => env('WEBSITE_DESIGN', 'new'),

    /*
    | The "Ask anything" pill (POST /api/website/ask): answers from the site's
    | own pages via the panels' model and key (config/gemini.php). Public, so
    | budgeted per visitor (IP) and for the whole site per day; the route also
    | allows 10 a minute per IP. 0 = no limit.
    */
    'ask' => [
        'enabled'             => env('WEBSITE_ASK_ENABLED', true),
        'per_visitor_per_day' => (int) env('WEBSITE_ASK_PER_VISITOR', 40),
        'per_day'             => (int) env('WEBSITE_ASK_PER_DAY', 1500),
        'history_turns'       => 8,
        'max_output_tokens'   => (int) env('WEBSITE_ASK_MAX_TOKENS', 700),
        // Whole question, all fallback models included — under nginx's 60 s.
        'seconds'             => (int) env('WEBSITE_ASK_SECONDS', 30),
        // How long the site's text is kept before it is read again.
        'knowledge_minutes'   => (int) env('WEBSITE_ASK_KNOWLEDGE_MINUTES', 360),
    ],

];
