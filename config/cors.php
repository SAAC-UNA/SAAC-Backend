<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // En desarrollo permitir orígenes específicos (no usar * con credentials)
    // En producción cambiar a los dominios específicos de la UNA
    'allowed_origins' => env('APP_ENV') === 'production' 
        ? explode(',', env('CORS_ALLOWED_ORIGINS', 'https://saac.una.cr'))
        : ['http://localhost:5173', 'http://localhost:3000', 'http://127.0.0.1:5173'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400, // 1 día — evita OPTIONS preflight repetido desde el SPA

    'supports_credentials' => true,

];
