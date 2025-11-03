# Middleware in LaraInk

LaraInk provides seamless integration with Laravel's middleware system, allowing you to protect and control access to your pages using the same middleware you use in your Laravel application.

## Overview

When you configure middleware on a LaraInk page using the `->middleware()` method, the package automatically:

1. **Registers the route in Laravel** with the specified middleware
2. **Applies authentication checks** on the server side
3. **Passes middleware information** to the frontend for UI logic

> Improve security by following the [Security Hardening](security-hardening.md) guide.

## Basic Usage

### Single Middleware

```php
<?php
ink_make()
    ->title('Admin Dashboard')
    ->middleware('admin');
?>

<div>
    <h1>Admin Dashboard</h1>
    <p>Only administrators can see this page.</p>
</div>
```

### Multiple Middlewares

You can apply multiple middlewares using array syntax:

```php
<?php
ink_make()
    ->title('Premium Content')
    ->middleware([
        'auth', 
        'verified', 
        'subscription:premium'
    ]);
?>

<div>
    <h1>Premium Content</h1>
    <p>This content requires authentication, email verification, and a premium subscription.</p>
</div>
```

## Authentication

### Simple Authentication

Use the `->auth()` method for basic authentication requirements:

```php
<?php
ink_make()
    ->title('User Profile')
    ->auth(true);
?>
```

This automatically applies Laravel Sanctum authentication (`auth:sanctum` middleware), and save bearer token in the frontend for future requests (inside `window.lara_ink.token`).

If user is not authenticated, it will redirect to the login page (which you can modify: /resources/lara-ink/pages/login.php).

> You can configure TTL (how many seconds the token is valid when is not been used) for the token in the `lara-ink.php` config file.

### Authentication + Additional Middleware

Combine `->auth()` with `->middleware()` for more specific requirements:

```php
<?php
ink_make()
    ->title('Team Management')
    ->auth(true)
    ->middleware(['role:manager', 'team.member']);
?>
```

## How It Works

### Server-Side Route Registration

When you run `php artisan lara-ink:build`, LaraInk:

1. Parses all page configurations
2. Registers Laravel routes for each page
3. Applies the configured middleware to each route

**Example Route Registration:**

For a page at `resources/lara-ink/pages/admin/users.php`:

```php
<?php
ink_make()
    ->middleware(['auth', 'role:admin']);
?>
```

LaraInk automatically registers:

```php
Route::middleware(['web', 'auth:sanctum', 'auth', 'role:admin'])
    ->get('/admin/users', function () {
        // Serves the compiled HTML
    })
    ->name('lara-ink.page.admin.users');
```

### Middleware Stack

The middleware stack is built as follows:

1. **`web`** - Always applied (session, CSRF, etc.)
2. **`auth:sanctum`** - Applied if `->auth(true)` or `->middleware()` is used
3. **Custom middlewares** - Your specified middlewares in order

## Common Use Cases

### Role-Based Access

```php
<?php
ink_make()
    ->title('Admin Panel')
    ->middleware('role:admin');
?>
```

### Permission-Based Access

```php
<?php
ink_make()
    ->title('Edit Post')
    ->middleware(['auth', 'can:edit,post']);
?>
```

### Subscription-Based Access

```php
<?php
ink_make()
    ->title('Premium Features')
    ->middleware(['auth', 'verified', 'subscription:premium']);
?>
```

### Team-Based Access

```php
<?php
ink_make()
    ->title('Team Dashboard')
    ->middleware(['auth', 'team.member']);
?>
```

### Rate Limiting

```php
<?php
ink_make()
    ->title('API Documentation')
    ->middleware('throttle:60,1');
?>
```

## Creating Custom Middleware

You can create custom middleware for LaraInk pages just like any Laravel middleware:

```bash
php artisan make:middleware CheckSubscription
```

**app/Http/Middleware/CheckSubscription.php:**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSubscription
{
    public function handle(Request $request, Closure $next, string $plan = 'basic'): mixed
    {
        // -- Your validation code here --
        // Example:
        if (!$request->user()?->hasActivePlan($plan)) {
            return response()->json([
                'message' => 'Subscription required'
            ], 403);
        }

        return $next($request);
    }
}
```

**Register in `bootstrap/app.php` (Laravel 11+):**

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'subscription' => \App\Http\Middleware\CheckSubscription::class,
    ]);
})
```

**Use in LaraInk page:**

```php
<?php
ink_make()
    ->title('Premium Content')
    ->middleware('subscription:premium');
?>
```

## Frontend Integration

The middleware configuration is also passed to the frontend as a JavaScript array, allowing you to implement UI logic:

```php
<?php
ink_make()
    ->middleware(['auth', 'verified']);
?>

<div x-data="pageData()">
    <template x-if="middleware.includes('verified')">
        <div class="verified-badge">✓ Verified User</div>
    </template>
</div>
```

## Best Practices

### 1. Use Specific Middleware

Instead of:
```php
->middleware('auth')
```

Prefer:
```php
->auth(true)  // More explicit for authentication
```

### 2. Order Matters

Place authentication middleware before authorization:

```php
->middleware(['auth', 'verified', 'role:admin'])
```

### 3. Combine with Cache Wisely

Be careful when combining middleware with caching:

```php
<?php
ink_make()
    ->middleware('role:admin')
    ->cache(false);  // Don't cache protected pages
?>
```

### 4. Test Your Middleware

Always test that your middleware is working correctly:

```bash
# Try accessing the page without authentication
curl http://your-app.test/admin/users

# Should return 401 Unauthorized
```

## Troubleshooting

### Middleware Not Applied

If middleware isn't being applied:

1. **Rebuild your application:**
   ```bash
   php artisan lara-ink:build
   ```

2. **Clear route cache:**
   ```bash
   php artisan route:clear
   ```

3. **Verify middleware is registered** in `bootstrap/app.php`

### 401 Unauthorized on Every Request

Check that:
- Laravel Sanctum is properly configured
- Bearer token is being sent in requests
- Token hasn't expired

### Custom Middleware Not Found

Ensure your middleware is:
1. Created in `app/Http/Middleware/`
2. Registered in `bootstrap/app.php`
3. Using the correct alias in `->middleware()`

## Advanced Examples

### Dynamic Middleware Based on Route Parameters

```php
<?php
// File: resources/lara-ink/pages/team/[id]/dashboard.php
ink_make()
    ->title('Team Dashboard')
    ->middleware(['auth', 'team.member']);
?>

<div x-data="pageData()">
    <h1>Team Dashboard</h1>
    <p>Team ID: <span x-text="request.id"></span></p>
</div>
```

### Combining Multiple Protection Layers

```php
<?php
ink_make()
    ->title('Sensitive Data')
    ->auth(true)
    ->middleware([
        'verified',
        'role:admin',
        'ip.whitelist',
        'throttle:10,1'
    ])
    ->cache(false);
?>
```

## API Reference

### `->middleware(string|array $middleware): LaraInk`

Configures middleware for the page.

**Parameters:**
- `$middleware` - String or array of middleware names

**Returns:** `LaraInk` instance for method chaining

**Examples:**

```php
// Single middleware
->middleware('admin')

// Multiple middlewares
->middleware(['auth', 'verified', 'role:admin'])

// Middleware with parameters
->middleware('throttle:60,1')
->middleware('can:edit,post')
->middleware('subscription:premium')
```

## Related Documentation

- [Authentication](authentication.md)
- [Page Configuration](page-configuration.md)
- [Routing](routing.md)
- [Laravel Middleware Documentation](https://laravel.com/docs/middleware)
