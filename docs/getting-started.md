# Getting Started with LaraInk

LaraInk transforms your Laravel application into a standalone SPA with Alpine.js, powered by a REST API with Bearer Token authentication.

## Installation

Install via Composer:

```bash
composer require b7s/lara-ink
```

Run the install command:

```bash
php artisan lara-ink:install
```

The package will automatically:
- Create necessary directories
- Publish configuration file
- Create default layout
- Publish starter pages (login and error pages)
- Copy Vite plugin to project root

## Directory Structure

After installation, you'll have:

```
resources/lara-ink/
├── pages/          # Your page files
├── layouts/        # Layout templates
├── components/     # Reusable components
└── assets/         # CSS, JS, images

public/lara-ink/
├── pages/          # Compiled HTML pages
└── build/          # Built assets
```

## Configuration

Configuration file: `config/lara-ink.php`

```php
return [
    'name' => '✒️ LaraInk',
    
    'api_base_url' => env('LARAINK_API_URL', null),
    
    'default_layout' => 'app',
    
    'output' => [
        'dir' => 'public/lara-ink',
        'pages_dir' => 'public/lara-ink/pages',
        'build_dir' => 'public/lara-ink/build',
    ],
    
    'cache' => [
        'enable' => true,
        'ttl' => 300,
    ],
    
    'auth' => [
        'route' => [
            'prefix' => '/api/ink',
            'login' => '/login',
            'unauthorized' => '/unauthorized',
        ],
        'token_ttl' => 900,
    ],
    
    'scripts' => [
        'beforeAlpine' => [
            // Scripts to load before Alpine.js (e.g., plugins)
        ],
        'alpinejs' => 'https://cdn.jsdelivr.net/npm/alpinejs@3.15.1/dist/cdn.min.js',
        'others' => [
            // Other scripts to load after Alpine.js
        ]
    ],
    
    'styles' => [
        'others' => [
            // Additional stylesheets
        ]
    ],
];
```

## Your First Page

Create a page at `resources/lara-ink/pages/welcome.php`:

```php
<?php
ink_make()
    ->layout('app')
    ->title('Welcome to LaraInk');
?>

<div x-data="{ message: 'Hello, LaraInk!' }">
    <h1 x-text="message"></h1>
    <button @click="message = 'You clicked me!'">
        Click me
    </button>
</div>
```

## Build Your SPA

Build your pages:

```bash
php artisan lara-ink:build
```

Output:
```
INFO  LaraInk - Building SPA bundle

✓ Build completed
  Build completed successfully
  1 page(s) compiled
```

Your compiled page will be at: `public/lara-ink/pages/welcome.html`

## Development Mode

For development with hot reload:

```bash
php artisan lara-ink:dev
```

This will:
- Build your pages initially
- Watch for changes in `resources/lara-ink/`
- Automatically rebuild when files change
- Show build status in terminal

## Accessing Pages

Pages are accessible at:
- `http://your-app.test/lara-ink/pages/welcome.html`
- Or configure your web server to serve them directly

## Default Pages

LaraInk includes two starter pages in `resources/lara-ink/pages/`:

### Login Page (`login.php`)
A beautiful, responsive login page with:
- Email and password authentication
- Bearer token handling
- Remember me functionality
- Form validation
- Loading states
- Fully customizable design

The login page expects your API to return a `token` field in the JSON response when credentials are valid.

### Error Page (`error.php`)
A dynamic error page that displays different messages based on HTTP status codes:
- 400, 401, 403, 404, 419, 429, 500, 503
- Receives error code via URL parameter: `/error?code=404`
- Beautiful gradient design
- Responsive layout
- Go back and go home actions

Both pages are fully customizable - modify them to match your application's design.

## Next Steps

- [Creating Pages](./pages.md)
- [Using Components](./components.md)
- [Working with Layouts](./layouts.md)
- [Development Workflow](./development.md)
- [Alpine.js Integration](./alpine.md)
