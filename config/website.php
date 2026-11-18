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

];
