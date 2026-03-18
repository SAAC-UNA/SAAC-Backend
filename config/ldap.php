<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default LDAP Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the LDAP connections below you wish
    | to use as your default connection for all LDAP operations. Of
    | course you may add as many connections you'd like below.
    |
    */

    'default' => env('LDAP_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | LDAP Connections
    |--------------------------------------------------------------------------
    |
    | Below you may configure each LDAP connection your application requires
    | access to. Be sure to include a valid base DN - otherwise you may
    | not receive any results when performing LDAP search operations.
    |
    */

    'connections' => [

        'default' => [
            'hosts' => [env('LDAP_HOST', '127.0.0.1')],
            'username' => env('LDAP_USERNAME', 'cn=user,dc=local,dc=com'),
            'password' => env('LDAP_PASSWORD', 'secret'),
            'port' => env('LDAP_PORT', 389),
            'base_dn' => env('LDAP_BASE_DN', 'dc=local,dc=com'),
            'timeout' => env('LDAP_TIMEOUT', 5),
            'use_ssl' => env('LDAP_USE_SSL', false),
            'use_tls' => env('LDAP_USE_TLS', false),
            'use_sasl' => env('LDAP_SASL', false),
            'sasl_options' => [
                // 'mech' => 'GSSAPI',
            ],
            'options' => [
                // Opciones de protocolo LDAP
                LDAP_OPT_PROTOCOL_VERSION => 3,
                LDAP_OPT_REFERRALS => 0,
                // Deshabilitar verificación de certificados SSL para desarrollo (certificados auto-firmados)
                // En producción con certificados válidos, cambiar a LDAP_OPT_X_TLS_DEMAND
                LDAP_OPT_X_TLS_REQUIRE_CERT => env('LDAP_SSL_VERIFY', false) ? LDAP_OPT_X_TLS_DEMAND : LDAP_OPT_X_TLS_NEVER,
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | LDAP Logging
    |--------------------------------------------------------------------------
    |
    | When LDAP logging is enabled, all LDAP search and authentication
    | operations are logged using the default application logging
    | driver. This can assist in debugging issues and more.
    |
    */

    'logging' => [
        'enabled' => env('LDAP_LOGGING', true),
        'channel' => env('LOG_CHANNEL', 'stack'),
        'level' => env('LOG_LEVEL', 'info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | LDAP Cache
    |--------------------------------------------------------------------------
    |
    | LDAP caching enables the ability of caching search results using the
    | query builder. This is great for running expensive operations that
    | may take many seconds to complete, such as a pagination request.
    |
    */

    'cache' => [
        'enabled' => env('LDAP_CACHE', false),
        'driver' => env('CACHE_DRIVER', 'file'),
    ],

    /*
    |--------------------------------------------------------------------------
    | LDAP Organizational Units
    |--------------------------------------------------------------------------
    |
    | Unidades organizacionales donde se buscará el DN del usuario al autenticar.
    | Para agregar un nuevo OU (ej. administrativos) basta con actualizar
    | LDAP_OUS en .env sin modificar el código fuente (OCP).
    |
    */
    'organizational_units' => array_filter(explode(',', env('LDAP_OUS', 'profesores,estudiantes'))),

];
