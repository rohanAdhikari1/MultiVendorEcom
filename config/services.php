<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways (Phase F)
    |--------------------------------------------------------------------------
    |
    | Nepal-specific payment gateway credentials. No live sandbox
    | credentials are configured out of the box — see .env.example for the
    | placeholder keys. base/form/status URLs default to each gateway's
    | public sandbox endpoints so local development targets sandbox unless
    | explicitly overridden.
    |
    */

    'esewa' => [
        'form_url' => env('ESEWA_FORM_URL', 'https://rc-epay.esewa.com.np/api/epay/main/v2/form'),
        'status_url' => env('ESEWA_STATUS_URL', 'https://rc-epay.esewa.com.np/api/epay/transaction/status/'),
        'product_code' => env('ESEWA_PRODUCT_CODE'),
        'secret_key' => env('ESEWA_SECRET_KEY'),
    ],

    'khalti' => [
        'base_url' => env('KHALTI_BASE_URL', 'https://dev.khalti.com/api/v2/'),
        'secret_key' => env('KHALTI_SECRET_KEY'),
    ],

    'fonepay' => [
        'base_url' => env('FONEPAY_BASE_URL', 'https://clientapi.fonepay.com/api/merchantRequest'),
        'merchant_code' => env('FONEPAY_MERCHANT_CODE'),
        'secret_key' => env('FONEPAY_SECRET_KEY'),
    ],

];
