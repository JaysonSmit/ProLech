<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ultramsg — WhatsApp Business API
    |--------------------------------------------------------------------------
    | Configura las credenciales en .env:
    |   ULTRAMSG_INSTANCE_ID=your_instance_id
    |   ULTRAMSG_TOKEN=your_token
    |
    | Obtén las credenciales en: https://ultramsg.com
    */
    'ultramsg' => [
        'instance_id' => env('ULTRAMSG_INSTANCE_ID'),
        'token'       => env('ULTRAMSG_TOKEN'),
    ],

    /*
    | Nubefact — boleta electrónica ante SUNAT. Sin estos valores la boleta
    | queda como representación interna ("simulada").
    */
    'nubefact' => [
        'url'   => env('NUBEFACT_URL'),
        'token' => env('NUBEFACT_TOKEN'),
    ],

];
