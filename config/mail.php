<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mailer por Defecto
    |--------------------------------------------------------------------------
    |
    | Esta opción controla el mailer predeterminado que se usa para enviar
    | todos los mensajes de correo a menos que se especifique explícitamente
    | otro mailer al enviar el mensaje. Todos los mailers adicionales se
    | pueden configurar en el array "mailers". Se proporcionan ejemplos.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Configuraciones de Mailers
    |--------------------------------------------------------------------------
    |
    | Aquí puedes configurar todos los mailers usados por tu aplicación
    | junto con sus respectivos ajustes. Se han configurado varios ejemplos
    | y eres libre de agregar los tuyos según lo requiera tu aplicación.
    |
    | Laravel soporta una variedad de drivers de "transporte" de correo que
    | pueden usarse al enviar un email. Puedes especificar cuál estás usando
    | para tus mailers abajo. También puedes agregar mailers adicionales.
    |
    | Soportados: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |             "postmark", "resend", "log", "array",
    |             "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
            'retry_after' => 60,
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
            'retry_after' => 60,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Dirección "From" Global
    |--------------------------------------------------------------------------
    |
    | Puedes desear que todos los correos enviados por tu aplicación se
    | envíen desde la misma dirección. Aquí puedes especificar un nombre
    | y dirección que se usa globalmente para todos los correos enviados
    | por tu aplicación.
    |
    */

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

];
