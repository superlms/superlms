<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Main application hosts
    |--------------------------------------------------------------------------
    | Hostnames that belong to the main SUPERLMS app itself (marketing site,
    | super-admin, school admin panels). Requests on these hosts are NEVER
    | treated as a school's custom-domain website. The app.url host is added
    | automatically; list any extra hosts here.
    */
    'main_hosts' => array_filter([
        'superlms.in',
        'www.superlms.in',
        env('SCHOOL_SITE_MAIN_HOST'),
    ]),

];
