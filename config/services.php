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
        'sub_super_admin_password_template_key' => env('ZEPTOMAIL_SUB_SUPER_ADMIN_TEMPLATE_KEY', '2518b.67c7a493957be4c4.k1.944e75c0-7ae7-11f1-84db-62df313bf14d.19f427eff1c'),
    ],

];
