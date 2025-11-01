# Page Configuration

Every LaraInk page starts with a configuration block that defines its behavior, layout, caching, and security settings.

## Basic Configuration

```php
<?php
ink_make()
    ->title('Page Title')
    ->layout('app');
?>
```

## Available Methods

### `title(string $title)`

Sets the page title that appears in the browser tab and SEO metadata.

```php
ink_make()->title('User Dashboard');
```

### `layout(string $layout)`

Specifies which layout template to use. Supports nested layouts with dot notation.

```php
// Uses resources/lara-ink/layouts/app.php
ink_make()->layout('app');

// Uses resources/lara-ink/layouts/dashboard/admin.php
ink_make()->layout('dashboard.admin');
```

### `cache(int|bool $ttl)`

Controls page caching behavior.

```php
// Cache for 10 minutes (600 seconds)
ink_make()->cache(600);

// Use default TTL from config
ink_make()->cache(true);

// Disable caching
ink_make()->cache(false);
```

**Cache with Carbon:**

```php
use Carbon\Carbon;

// Cache until specific time
ink_make()->cache(Carbon::now()->addHours(2));

// Cache for a duration
ink_make()->cache(new DateInterval('PT1H')); // 1 hour
```

### `auth(bool $required)`

Requires user authentication to access the page.

```php
ink_make()->auth(true);
```

When enabled:
- Unauthenticated users are redirected to login
- Bearer token is validated on page load
- Token refresh happens automatically

### `middleware(string $middleware)`

Applies custom middleware to the page.

```php
ink_make()->middleware('admin');
```

### `seo(...)`

Configures SEO metadata for the page.

**Simple usage:**

```php
ink_make()->seo(
    title: 'My Awesome Page',
    description: 'This page is awesome',
    keywords: 'awesome, page, laravel',
    image: '/images/og-image.jpg'
);
```

**Advanced usage with SeoConfig:**

```php
use B7s\LaraInk\DTOs\SeoConfig;

ink_make()->seo(new SeoConfig(
    title: 'My Awesome Page',
    description: 'This page is awesome',
    keywords: 'awesome, page, laravel',
    image: '/images/og-image.jpg',
    canonical: 'https://example.com/awesome',
    robots: 'index, follow',
    meta: [
        'author' => 'Bruno Tenorio',
        'theme-color' => '#4F46E5',
    ],
    og: [
        'type' => 'website',
        'locale' => 'en_US',
    ],
    twitter: [
        'card' => 'summary_large_image',
        'site' => '@laraink',
    ]
));
```

## Complete Example

```php
<?php
use Carbon\Carbon;

ink_make()
    ->title('Admin Dashboard')
    ->layout('dashboard.admin')
    ->cache(Carbon::now()->addMinutes(30))
    ->auth(true)
    ->middleware('admin')
    ->seo(
        title: 'Admin Dashboard - LaraInk',
        description: 'Manage your application',
        keywords: 'admin, dashboard, management',
        image: '/images/admin-og.jpg',
        robots: 'noindex, nofollow'
    );
?>

<div class="dashboard">
    <h1>Welcome to Admin Dashboard</h1>
    <!-- Your content here -->
</div>
```

## Configuration File

Default values are set in `config/lara-ink.php`:

```php
return [
    'default_layout' => 'app',
    
    'cache' => [
        'enable' => true,
        'ttl' => 300, // 5 minutes
    ],
    
    'auth' => [
        'route' => [
            'prefix' => '/api/ink',
            'login' => '/login',
            'unauthorized' => '/unauthorized',
        ],
        'token_ttl' => 900, // 15 minutes
    ],
];
```

## Best Practices

### 1. Use Descriptive Titles

```php
// ❌ Bad
ink_make()->title('Page');

// ✅ Good
ink_make()->title('User Profile - Settings');
```

### 2. Cache Static Pages

```php
// For pages that rarely change
ink_make()->cache(3600); // 1 hour
```

### 3. Secure Sensitive Pages

```php
// Always require auth for admin pages
ink_make()
    ->auth(true)
    ->middleware('admin');
```

### 4. Optimize SEO

```php
// Include relevant metadata
ink_make()->seo(
    title: 'Unique, descriptive title',
    description: 'Clear description under 160 characters',
    keywords: 'relevant, keywords, here',
    image: '/path/to/social-share-image.jpg'
);
```

## Next Steps

- [Blade Directives](blade-directives.md) - Learn supported Blade syntax
- [Routing](routing.md) - Set up dynamic routes
- [Authentication](authentication.md) - Implement user authentication
