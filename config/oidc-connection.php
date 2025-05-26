<?php

return [
    'signing_key_path' => env('OIDC_SIGNING_KEY_FILE_PATH', 'private-sign-keys/demo-private-sign.pem'),
    'signing_key_public_path' => env('OIDC_SIGNING_KEY_PUBLIC_FILE_PATH', 'public-sign-keys/demo-public-sign.pem'),
    'signing_key_id' => env('OIDC_SIGNING_KEY_ID', 'demo-sign'),
    'token_endpoint_auth_method' => env('OIDC_TOKEN_ENDPOINT_AUTH_METHOD', 'client_secret_post'),
];
