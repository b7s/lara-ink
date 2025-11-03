<?php

declare(strict_types=1);

return [
    'name' => '✒️ LaraInk',

    'api_base_url' => env('LARAINK_API_URL', null), // If null, use "url" from "config/app.php"

    'default_layout' => 'app', // the "app.php" file inside "resources/lara-ink/layouts/"

    'output' => [
        'dir' => 'public/lara-ink', // your-project-root-dir/public/lara-ink
        'pages_dir' => 'public/lara-ink/pages',
        'build_dir' => 'public/lara-ink/build',
    ],

    'cache' => [
        'enable' => true,
        'ttl' => 300,
    ],

    'auth' => [
        'route' => [
            'api_prefix' => '/api/ink',
            // Send user to this routes
            'login' => '/login',
            'unauthorized' => '/unauthorized',
            'authorize_api' => '/authorize',
        ],
        'token_ttl' => 900, // Token expiration time in seconds
    ],

    // Include on project
    'scripts' => [
        'beforeAlpine' => [
            'https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.15.1/dist/cdn.min.js', // Required
        ],
        'alpinejs' => 'https://cdn.jsdelivr.net/npm/alpinejs@3.15.1/dist/cdn.min.js', // Required
        'others' => []
    ],

    'styles' => [ // Set url to include on bundle
        'others' => []
    ],
];
