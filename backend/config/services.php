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

    'payphone' => [
        'token' => env('PAYPHONE_TOKEN'),
        'store_id' => env('PAYPHONE_STORE_ID'),
    ],

    'fcm' => [
        // JSON de la cuenta de servicio de Firebase, en base64. Ver .env.
        // Va en base64 y no como archivo porque .env esta en .dockerignore
        // (asi la clave privada no entra en la imagen) y porque el --env-file
        // de Docker no interpreta comillas ni escapes: un PEM multilinea o un
        // valor entrecomillado se leerian mal.
        'credentials' => env('FIREBASE_CREDENTIALS_BASE64'),
    ],

];
