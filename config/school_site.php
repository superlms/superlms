<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Main application hosts
    |--------------------------------------------------------------------------
    | Hostnames that belong to the main EDYONE app itself (marketing site,
    | super-admin, school admin panels). Requests on these hosts are NEVER
    | treated as a school's custom-domain website. The app.url host is added
    | automatically; list any extra hosts here.
    */
    'main_hosts' => array_filter([
        'edyonelms.in',
        'www.edyonelms.in',
        env('SCHOOL_SITE_MAIN_HOST'),
    ]),

];
