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

    'strava' => [
        'client_id' => env('STRAVA_CLIENT_ID'),
        'client_secret' => env('STRAVA_CLIENT_SECRET'),
        'redirect' => env('STRAVA_REDIRECT_URI'),
        'base_url' => env('STRAVA_BASE_URL', 'https://www.strava.com/api/v3'),
        'authorize_url' => env('STRAVA_AUTHORIZE_URL', 'https://www.strava.com/oauth/authorize'),
        'token_url' => env('STRAVA_TOKEN_URL', 'https://www.strava.com/oauth/token'),
    ],

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
        'version' => env('ANTHROPIC_VERSION', '2023-06-01'),
    ],

    'embeddings' => [
        'provider' => env('EMBEDDINGS_PROVIDER', 'openai'),
        'key' => env('EMBEDDINGS_API_KEY'),
        'base_url' => env('EMBEDDINGS_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('EMBEDDINGS_MODEL', 'text-embedding-3-small'),
        'dimensions' => (int) env('EMBEDDINGS_DIMENSIONS', 1536),
    ],

];
