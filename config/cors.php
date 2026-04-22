<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie'
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://fortaleza.keepcomply.co.ao',
        'http://aml.nossaseguros.ao',
        'https://qualidade-aml.nossaseguros.ao',
        'http://172.18.100.10:8081',
        'http://172.18.100.21:8081',
        'https://172.18.100.10:1025',
        'https://172.18.100.20:1025',
        'https://172.18.100.21:1025',
        'http://172.17.100.11:1025',
        'http://172.17.100.12:8081',
        'localhost:5173'
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'X-XSRF-TOKEN'
    ],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => true,

];
