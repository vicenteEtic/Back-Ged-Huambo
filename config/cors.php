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
        'https://nossa-denuncias.keepcomply.co.ao',
        'https://qualidade-aml.nossaseguros.ao',
        'http://172.18.100.10',
        'http://172.18.100.21',
        'https://172.18.100.10',
        'https://172.18.100.20',
        'https://172.18.100.21',
        'http://172.17.100.11:1025',
        'http://172.17.100.12:8081',
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
