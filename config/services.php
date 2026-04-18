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

    'cross_service_sso' => [
        'secret' => env('CROSS_SERVICE_SSO_SECRET'),
        'ttl_seconds' => (int) env('CROSS_SERVICE_SSO_TTL_SECONDS', 90),
        'map_service_url' => env('MAP_SERVICE_URL', 'https://map.blagokirov.ru'),
    ],

    'google_business_profile' => [
        'enabled' => (bool) env('GOOGLE_BUSINESS_PROFILE_ENABLED', false),
        'account_id' => env('GOOGLE_BUSINESS_PROFILE_ACCOUNT_ID'),
        'location_id' => env('GOOGLE_BUSINESS_PROFILE_LOCATION_ID'),
        'client_id' => env('GOOGLE_BUSINESS_PROFILE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_BUSINESS_PROFILE_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_BUSINESS_PROFILE_REFRESH_TOKEN'),
        'reviews_limit' => (int) env('GOOGLE_BUSINESS_PROFILE_REVIEWS_LIMIT', 6),
        'cache_minutes' => (int) env('GOOGLE_BUSINESS_PROFILE_CACHE_MINUTES', 30),
        'timeout_seconds' => (int) env('GOOGLE_BUSINESS_PROFILE_TIMEOUT_SECONDS', 10),
        'api_base_url' => env('GOOGLE_BUSINESS_PROFILE_API_BASE_URL', 'https://mybusiness.googleapis.com/v4'),
        'token_url' => env('GOOGLE_BUSINESS_PROFILE_TOKEN_URL', 'https://oauth2.googleapis.com/token'),
        'oauth_authorize_url' => env('GOOGLE_BUSINESS_PROFILE_OAUTH_AUTHORIZE_URL', 'https://accounts.google.com/o/oauth2/v2/auth'),
        'oauth_redirect_uri' => env('GOOGLE_BUSINESS_PROFILE_OAUTH_REDIRECT_URI'),
    ],

    'bitrix24' => [
        'base_url' => env('BITRIX24_BASE_URL'),
    ],

];
