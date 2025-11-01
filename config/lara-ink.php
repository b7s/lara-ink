<?php

declare(strict_types=1);

return [
    'name' => '✒️ LaraInk',

    'api_base_url' => env('LARAINK_API_URL', null), // If null, use "url" from "config/app.php"

    'default_layout' => 'app', // the "app.php" file inside "resources/lara-ink/layouts/"

    'output' => [
        'dir' => 'public', // your-project-root-dir/public
        'pages_dir' => 'public/pages', // your-project-root-dir/public/pages
        'build_dir' => 'public/build', // your-project-root-dir/public/build
    ],

    'cache' => [
        'enable' => true,
        'ttl' => 300,
    ],

    'auth' => [
        'route' => [
            'prefix' => '/api/ink',
            // Send user to this routes
            'login' => '/login',
            'unauthorized' => '/unauthorized',
            'authorize_api' => '/authorize',
        ],
        'token_ttl' => 900, // Token expiration time in seconds
    ],

    // Include on project
    'scripts' => [
        'alpinejs' => 'https://cdn.jsdelivr.net/npm/alpinejs@3.15.1/dist/cdn.min.js', // required
        'others' => [ // array of links or path to script to include on bundle
        ]
    ],

    'styles' => [
        'others' => [ // array of links or path to style to include on bundle
        ]
    ],
];