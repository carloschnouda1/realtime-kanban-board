<?php

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Comma-separated list of frontend origins, e.g. "http://localhost:3000".
    'allowed_origins' => array_filter(explode(',', env('FRONTEND_URL', 'http://localhost:3000'))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
