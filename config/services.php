<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'firebase' => [
        'credentials' => public_path('superlms-lms-firebase-adminsdk-fbsvc-b592c9fade.json'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'database_url' => env('FIREBASE_DATABASE_URL'),
    ],

    'phonepe' => [
        // PhonePe Standard Checkout (OAuth-based) credentials.
        // Get these from PhonePe Business → Developer Settings → API Keys.
        'client_id'      => env('PHONEPE_CLIENT_ID'),
        'client_secret'  => env('PHONEPE_CLIENT_SECRET'),
        'client_version' => env('PHONEPE_CLIENT_VERSION', '1'),
        // 'sandbox' (Test Mode) or 'production'.
        'env'            => env('PHONEPE_ENV', 'sandbox'),
        // Webhook auth — set the SAME username/password in
        // Developer Settings → Webhooks on the PhonePe dashboard.
        'webhook_username' => env('PHONEPE_WEBHOOK_USERNAME'),
        'webhook_password' => env('PHONEPE_WEBHOOK_PASSWORD'),
    ],

    // WhatsApp Cloud API (Meta) — the welcome message a new student or
    // teacher gets beside the email. Off until the token and number are set.
    'whatsapp' => [
        'enabled'         => env('WHATSAPP_ENABLED', true),
        'token'           => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'api_version'     => env('WHATSAPP_API_VERSION', 'v21.0'),
        'language'        => env('WHATSAPP_TEMPLATE_LANGUAGE', 'en'),
        'templates'       => [
            'student' => env('WHATSAPP_TEMPLATE_STUDENT', 'admission_confirmed'),
            'teacher' => env('WHATSAPP_TEMPLATE_TEACHER', 'teacher_joining_confirmed'),
            // School messages, charged to the school (App\Services\SchoolWhatsApp).
            'absent'  => env('WHATSAPP_TEMPLATE_ABSENT', 'student_absent_alert'),
            'fee'     => env('WHATSAPP_TEMPLATE_FEE', 'fee_received'),
        ],
        // Meta's status webhook (/api/whatsapp/webhook): the token Meta echoes
        // when the callback is set up, and the app secret its posts are signed with.
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
        'app_secret'           => env('WHATSAPP_APP_SECRET'),
        // The value for the templates' dynamic Download Now button (index 1),
        // appended to its Play Store link. Empty when that button is static.
        'download_button_suffix' => env('WHATSAPP_DOWNLOAD_BUTTON_SUFFIX', 'wa'),
        // Where the page's download button goes.
        'app_url'         => env('APP_PLAY_STORE_URL', 'https://play.google.com/store/apps/details?id=com.edyoneapp'),
    ],

    // SMS through Bhash SMS (App\Services\SmsService). A school the super
    // admin turns SMS on for gets the welcome by SMS only — no welcome email,
    // no WhatsApp (App\Services\WelcomeSms). Nothing goes until user, pass and
    // sender are set; until then every school keeps the email and WhatsApp.
    'bhash_sms' => [
        'enabled'  => env('BHASH_SMS_ENABLED', true),
        'url'      => env('BHASH_SMS_URL', 'https://bhashsms.com/api/sendmsg.php'),
        'user'     => env('BHASH_SMS_USER'),
        'pass'     => env('BHASH_SMS_PASS'),
        'sender'   => env('BHASH_SMS_SENDER'),            // the 6-letter DLT header
        'priority' => env('BHASH_SMS_PRIORITY', 'ndnd'),
        'stype'    => env('BHASH_SMS_STYPE', 'normal'),
        // DLT: the entity (PE) id, and what the account's API calls it and a
        // template's id. Each goes only when it has a value; change the names
        // if the API page in the Bhash panel calls them something else.
        'entity_id'      => env('BHASH_SMS_ENTITY_ID'),
        'entity_param'   => env('BHASH_SMS_ENTITY_PARAM', 'entityid'),
        'template_param' => env('BHASH_SMS_TEMPLATE_PARAM', 'templateid'),
        // Each text must be the DLT-approved template word for word, every
        // {#var#} filled in the order WelcomeSms gives (each 30 characters at
        // most). The link is fixed in the text, as DLT wants, and must be
        // whitelisted there; /app sends it on to the Play Store. An override
        // in .env must be in double quotes (it has #).
        'templates' => [
            // name, school, class, section, admission no., password
            'student_welcome' => [
                'id'   => env('BHASH_SMS_TEMPLATE_STUDENT'),
                'text' => env('BHASH_SMS_TEXT_STUDENT', 'Dear {#var#}, welcome to {#var#}. Class: {#var#}, Section: {#var#}. Login - Admission No: {#var#}, Password: {#var#}. Download the app: https://superlms.in/app - SuperLMS'),
            ],
            // name, school, username, password
            'teacher_welcome' => [
                'id'   => env('BHASH_SMS_TEMPLATE_TEACHER'),
                'text' => env('BHASH_SMS_TEXT_TEACHER', 'Dear {#var#}, welcome to {#var#}. Login - Username: {#var#}, Password: {#var#}. Download the app: https://superlms.in/app - SuperLMS'),
            ],
        ],
    ],

    'zeptomail' => [
        'api_url' => env('ZEPTOMAIL_API_URL', 'https://api.zeptomail.in/v1.1'),
        'api_token' => env('ZEPTOMAIL_API_TOKEN'),
        'from_email' => env('ZEPTOMAIL_FROM_EMAIL', 'noreply@superlms.in'),
        'from_name' => env('ZEPTOMAIL_FROM_NAME', 'SuperLMS'),
        'bounce_address' => env('ZEPTOMAIL_BOUNCE_ADDRESS'),

        // Template keys - add new ones here as you create them in Zoho
        'otp_template_key' => env('ZEPTOMAIL_OTP_TEMPLATE_KEY'),
        'teacher_password_template_key' => env('ZEPTOMAIL_TEACHER_PASSWORD_TEMPLATE_KEY'),
        'student_password_template_key' => env('ZEPTOMAIL_STUDENT_PASSWORD_TEMPLATE_KEY'),
        'welcome_template_key' => env('ZEPTOMAIL_WELCOME_TEMPLATE_KEY'),
        'fee_receipt_template_key' => env('ZEPTOMAIL_FEE_RECEIPT_TEMPLATE_KEY'),
        'announcement_template_key' => env('ZEPTOMAIL_ANNOUNCEMENT_TEMPLATE_KEY'),
        'password_changed_template_key' => env('ZEPTOMAIL_PASSWORD_CHANGED_TEMPLATE_KEY'),
        'school_creation_template_key'  => env('ZEPTOMAIL_SCHOOL_CREATION_TEMPLATE_KEY'),
        // `?:` so an empty ZEPTOMAIL_SUB_SUPER_ADMIN_TEMPLATE_KEY= line (as in
        // .env.example) keeps the built-in template instead of blanking it.
        'sub_super_admin_password_template_key' => env('ZEPTOMAIL_SUB_SUPER_ADMIN_TEMPLATE_KEY') ?: '2518b.67c7a493957be4c4.k1.944e75c0-7ae7-11f1-84db-62df313bf14d.19f427eff1c',
        // School sub-admins created from the admin panel's Users screen.
        'sub_admin_password_template_key' => env('ZEPTOMAIL_SUB_ADMIN_TEMPLATE_KEY', '2518b.67c7a493957be4c4.k1.f5b13d10-aa13-11f1-8b6f-62df313bf14d.1a077a6c361'),
    ],

    'otp' => [
        // Login 2-step (OTP) verification for the super-admin / admin / accounts
        // panels. Re-enabled — ZeptoMail is healthy again (and OTP falls back to
        // app SMTP if a send fails). Set LOGIN_OTP_ENABLED=false to skip the OTP
        // step in an emergency.
        'login_enabled' => env('LOGIN_OTP_ENABLED', true),
    ],

];
