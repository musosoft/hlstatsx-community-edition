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

    'steam' => [
        'api_key' => env('STEAM_API_KEY'),
    ],

    'hlstats' => [
        'daemon_host'   => env('HLSTATS_DAEMON_HOST', '127.0.0.1'),
        'daemon_port'   => env('HLSTATS_DAEMON_PORT', 27500),
        'history_days'  => env('HLSTATS_HISTORY_DAYS', 28),
        'site_name'     => env('HLSTATS_SITE_NAME', 'HLStatsX: CE'),
        'site_subtitle' => env('HLSTATS_SITE_SUBTITLE', ''),
    ],

    'github' => [
        'token' => env('GITHUB_TOKEN'),
    ],

];
