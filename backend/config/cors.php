<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Le frontend (SPA React) est servi depuis une origine distincte du backend.
    | On autorise donc les requêtes Cross-Origin vers l'API. L'authentification
    | se faisant par token Bearer (et non par cookie de session), il n'est pas
    | nécessaire d'activer « supports_credentials ».
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Origines autorisées : liste séparée par des virgules dans FRONTEND_URL.
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('FRONTEND_URL', 'http://localhost:5173'))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
