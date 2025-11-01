# Getting Started with LaraInk

## Introduction

LaraInk is a powerful DSL compiler that bridges the gap between Laravel's elegant Blade syntax and modern frontend reactivity with Alpine.js. It compiles your Blade-like pages into a standalone Single Page Application (SPA) that communicates with your Laravel backend via REST API.

## Requirements

- PHP >= 8.3
- Laravel >= 11.0
- Composer
- Node.js & NPM (for Vite)

## Installation

### Step 1: Install via Composer

```bash
composer require b7s/lara-ink
```

The package will automatically scaffold the required structure:

```
resources/lara-ink/
├── pages/          # Your page files
├── layouts/        # Layout templates
└── assets/         # Base JS and CSS

public/
├── build/          # Compiled assets
├── pages/          # Compiled pages
└── index.html      # SPA entry point

config/
└── lara-ink.php    # Configuration file
```

### Step 2: Configure Your Environment

Add to your `.env`:

```env
LARAINK_API_URL=http://your-api.test
```

### Step 3: Set Up Sanctum (Optional, for Authentication)

If you plan to use authentication:

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\ServiceProvider"
php artisan migrate
```

Add Sanctum middleware to `app/Http/Kernel.php`:

```php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

## Your First Page

### Create a Simple Page

Create `resources/lara-ink/pages/hello.php`:

```php
<?php
ink_make()
    ->title('Hello World')
    ->layout('app');
?>

<div class="container">
    <h1>Hello, LaraInk!</h1>
    <p>This is your first page.</p>
</div>
```

### Build the SPA

```bash
php artisan lara-ink:build
```

You should see output like:

```
LaraInk: Preparing LaraInk scaffolding...
LaraInk: LaraInk scaffolding finished successfully.
```

### Access Your Page

Open your browser and navigate to:

```
http://your-app.test/index.html#/hello
```

## Understanding the Structure

### Page Configuration Block

Every page starts with a PHP configuration block:

```php
<?php
ink_make()
    ->title('Page Title')
    ->layout('app')
    ->cache(600)
    ->auth(true);
?>
```

### Blade Content

After the configuration, write standard Blade markup:

```php
<div x-data="{ count: 0 }">
    <button @click="count++">Increment</button>
    <span x-text="count"></span>
</div>
```

### Alpine.js Integration

LaraInk automatically converts Blade directives to Alpine.js:

| Blade | Alpine.js |
|-------|-----------|
| `{{ $var }}` | `<span x-text="var"></span>` |
| `@if($condition)` | `<template x-if="condition">` |
| `@foreach($items as $item)` | `<template x-for="item in items">` |

## Next Steps

- [Page Configuration](page-configuration.md) - Learn about all configuration options
- [Blade Directives](blade-directives.md) - Explore supported Blade syntax
- [Routing](routing.md) - Set up dynamic routes
- [Authentication](authentication.md) - Secure your pages
- [API Integration](api-integration.md) - Connect to your Laravel backend

## Common Issues

### Build Command Not Found

Make sure the package is properly installed:

```bash
composer dump-autoload
php artisan list
```

### Pages Not Loading

Check that:
1. Build command completed successfully
2. `public/pages/` directory contains compiled pages
3. `public/index.html` exists
4. Your web server is configured correctly

### Alpine.js Not Working

Ensure Alpine.js is loaded in your layout:

```html
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
```

## Support

For issues and questions:
- [GitHub Issues](https://github.com/b7s/lara-ink/issues)
- [Documentation](../README.md)
