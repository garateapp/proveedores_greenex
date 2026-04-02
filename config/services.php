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

    'greenexnet' => [
        'base_url' => env('GREENEXNET_BASE_URL', 'https://greenexnet.test'),
        'entidades_path' => env('GREENEXNET_ENTIDADES_PATH', '/api/entidads'),
        'dias_trabajados_path' => env('GREENEXNET_DIAS_TRABAJADOS_PATH', '/api/attendances/dias-trabajados'),
        'token' => env('GREENEXNET_TOKEN'),
        'timeout' => (int) env('GREENEXNET_TIMEOUT', 6),
        'connect_timeout' => (int) env('GREENEXNET_CONNECT_TIMEOUT', 2),
        'verify_ssl' => (bool) env('GREENEXNET_VERIFY_SSL', true),
    ],

];
