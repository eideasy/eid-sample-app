<?php

return [
    'client_id'     => env('EID_CLIENT_ID', '2IaeiZXbcKzlP1KvjZH9ghty2IJKM8Lg'),
    'secret'        => env('EID_SECRET', '56RkLgZREDi1H0HZAvzOSAVlxu1Flx41'),
    'api_url'       => env('EID_API_URL', 'https://test.eideasy.com'),
    'card_domain'   => env('EID_CARD_DOMAIN', '.test.eideasy.localhost'),
    'redirect_uri'  => env('EID_REDIRECT_URI', 'http://eideasy-test.localhost'),
    'pades_api_uri' => env('EID_PADES_API_URL', 'https://detached-pdf.eideasy.com'),
];
