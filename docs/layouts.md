# Layouts

Layouts provide the HTML structure that wraps your page content. They define the common elements like header, footer, navigation, and scripts.

## Creating Layouts

Create layouts in `resources/lara-ink/layouts/`:

```
resources/lara-ink/layouts/
├── app.php
├── admin.php
└── guest.php
```

## Basic Layout

**File:** `resources/lara-ink/layouts/app.php`

```php
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? config('app.name') }}</title>
    {!! $head ?? '' !!}
</head>
<body>
    <header>
        <nav>
            <!-- Navigation -->
        </nav>
    </header>

    <main>
        @yield('page')
    </main>

    <footer>
        <!-- Footer content -->
    </footer>
</body>
</html>
```

## Using Layouts

Specify layout in your page:

```php
<?php
ink_make()->layout('app');
?>

<div>
    <h1>Page Content</h1>
</div>
```

## Layout Variables

Layouts have access to several variables:

### Available Variables

```php
{{ $title }}              // Page title
{{ $pageTitle }}          // Alternative title
{!! $head !!}             // Additional head content
{!! $pageMeta !!}         // Meta tags from page
{!! $pageStyles !!}       // Styles from page
{!! $pageCss !!}          // Inline CSS from page
{!! $pageJs !!}           // Page JavaScript
{!! $seoMetaTags !!}      // SEO meta tags
{!! $seoStructuredData !!} // JSON-LD structured data
{{ $alpineData }}         // Alpine.js initialization
{{ $config }}             // Page configuration object
{{ $page }}               // Full page object
```

### Using Variables

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $pageTitle ?? $title ?? 'LaraInk' }}</title>
    
    @if(!empty($seoMetaTags))
        {!! $seoMetaTags !!}
    @endif
    
    {!! $pageMeta ?? '' !!}
    
    <link rel="stylesheet" href="/css/app.css">
    
    {!! $pageStyles ?? '' !!}
    
    @if(!empty($pageCss))
        <style>{{ $pageCss }}</style>
    @endif
</head>
<body>
    @yield('page')
    
    @if(!empty($pageJs))
        <script>{!! $pageJs !!}</script>
    @endif
</body>
</html>
```

## Default Layout

LaraInk provides a default layout that includes:
- Alpine.js
- Vite assets
- Translation support
- SPA routing
- Authentication helpers

**Location:** `vendor/b7s/lara-ink/stubs/default-layout.blade.php`

You can override it by creating your own layout.

## Multiple Layouts

### Admin Layout

**File:** `resources/lara-ink/layouts/admin.php`

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - {{ $title }}</title>
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body class="admin-layout">
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <nav>
                <a href="/admin/dashboard">Dashboard</a>
                <a href="/admin/users">Users</a>
                <a href="/admin/settings">Settings</a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="admin-header">
                <h1>{{ $title }}</h1>
            </header>
            
            <div class="content">
                @yield('page')
            </div>
        </main>
    </div>
    
    <script src="/js/admin.js"></script>
    {!! $pageJs ?? '' !!}
</body>
</html>
```

**Usage:**

```php
<?php
ink_make()
    ->layout('admin')
    ->title('User Management');
?>

<div>
    <h2>Users</h2>
    <!-- User management content -->
</div>
```

### Guest Layout

**File:** `resources/lara-ink/layouts/guest.php`

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="/css/guest.css">
</head>
<body class="guest-layout">
    <div class="guest-container">
        <div class="guest-card">
            @yield('page')
        </div>
    </div>
</body>
</html>
```

**Usage:**

```php
<?php
ink_make()
    ->layout('guest')
    ->title('Login');
?>

<div>
    <h2>Login</h2>
    <x-forms.login-form />
</div>
```

## Nested Layouts

You can nest layouts using `@include`:

**File:** `resources/lara-ink/layouts/admin.php`

```php
@include('lara-ink::layouts.base', [
    'bodyClass' => 'admin-layout'
])
```

## Layout Components

Include reusable layout parts:

**File:** `resources/lara-ink/layouts/app.php`

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
</head>
<body>
    <x-layout.header />
    
    <main>
        @yield('page')
    </main>
    
    <x-layout.footer />
</body>
</html>
```

**Component:** `resources/lara-ink/components/layout/header.php`

```php
<header class="site-header">
    <div class="container">
        <a href="/" class="logo">LaraInk</a>
        <nav>
            <a href="/">Home</a>
            <a href="/about">About</a>
            <a href="/contact">Contact</a>
        </nav>
    </div>
</header>
```

## Conditional Content

Show different content based on page configuration:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
</head>
<body>
    @if($config->requiresAuth ?? false)
        <x-layout.authenticated-header />
    @else
        <x-layout.guest-header />
    @endif
    
    <main>
        @yield('page')
    </main>
</body>
</html>
```

## Scripts and Styles

### Including Scripts

```php
<head>
    <!-- Vite Assets -->
    <link rel="stylesheet" href="{{ $vite->getAssetUrl('app.css') }}">
    
    <!-- Additional Styles -->
    @if(ink_config('styles.others'))
        @foreach(ink_config('styles.others') as $stylePath)
            <link rel="stylesheet" href="/{{ ltrim($stylePath, '/') }}">
        @endforeach
    @endif
    
    <!-- Page Styles -->
    {!! $pageStyles ?? '' !!}
    
    <!-- Alpine.js Plugins (before Alpine) -->
    @if(ink_config('scripts.beforeAlpine'))
        @foreach(ink_config('scripts.beforeAlpine') as $scriptUrl)
            <script defer src="{{ ink_cached_script($scriptUrl) }}"></script>
        @endforeach
    @endif
    
    <!-- Alpine.js -->
    @if($alpineUrl = ink_config('scripts.alpinejs'))
        <script defer src="{{ ink_cached_script($alpineUrl) }}"></script>
    @endif
</head>
<body>
    @yield('page')
    
    <!-- Vite JS -->
    <script src="{{ $vite->getAssetUrl('app.js') }}"></script>
    
    <!-- Other Scripts -->
    @if(ink_config('scripts.others'))
        @foreach(ink_config('scripts.others') as $scriptPath)
            <script src="/{{ ltrim($scriptPath, '/') }}"></script>
        @endforeach
    @endif
    
    <!-- Page JS -->
    @if(!empty($pageJs))
        <script>{!! $pageJs !!}</script>
    @endif
</body>
```

## SEO in Layouts

Include SEO meta tags:

```php
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>{{ $pageTitle ?? $title ?? config('app.name') }}</title>
    
    <!-- SEO Meta Tags -->
    @if(!empty($seoMetaTags))
        {!! $seoMetaTags !!}
    @endif
    
    <!-- Page Meta -->
    {!! $pageMeta ?? '' !!}
    
    <!-- Structured Data -->
    @if(!empty($seoStructuredData))
        {!! $seoStructuredData !!}
    @endif
</head>
```

## Authentication Helper

Include authentication state:

```php
<body>
    <script>
        window.__LARA_INK_CONFIG__ = {
            api_base_url: '{{ ink_config("api_base_url") ?: config('app.url') }}{{ ink_config('auth.route.api_prefix', '/api/ink') }}',
            login_route: '{{ ink_config('auth.route.login', '/login') }}',
            unauthorized_route: '{{ ink_config('auth.route.unauthorized', '/unauthorized') }}',
            spa_mode: {{ isset($userLayout) ? 'false' : 'true' }}
        };
    </script>
    
    <script src="/build/lara-ink-spa.js"></script>
    
    <script>
        window.lara_ink = window.lara_ink || {};
        Object.assign(window.lara_ink, {
            api_base_url: window.__LARA_INK_CONFIG__.api_base_url,
            token: localStorage.getItem('lara_ink_token') || null,
            
            async newReq(endpoint, options = {}) {
                // API request helper
            },
            
            async is_authenticated() {
                // Check authentication
            }
        });
    </script>
</body>
```

## Best Practices

1. **Keep layouts simple**: Focus on structure, not content
2. **Use components**: Extract reusable parts into components
3. **Provide defaults**: Always have fallback values
4. **Document variables**: Comment what variables are expected
5. **Test with different pages**: Ensure layout works with all page types
6. **Optimize scripts**: Load scripts in correct order
7. **Mobile-first**: Ensure responsive design in layout

## Example: Complete Layout

```php
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>{{ $pageTitle ?? $title ?? config('app.name') }}</title>
    
    @if(!empty($seoMetaTags))
        {!! $seoMetaTags !!}
    @endif
    
    {!! $pageMeta ?? '' !!}
    
    <link rel="stylesheet" href="{{ $vite->getAssetUrl('app.css') }}">
    {!! $pageStyles ?? '' !!}
    
    @if(!empty($pageCss))
        <style>{{ $pageCss }}</style>
    @endif
    
    @if(ink_config('scripts.beforeAlpine'))
        @foreach(ink_config('scripts.beforeAlpine') as $scriptUrl)
            <script defer src="{{ ink_cached_script($scriptUrl) }}"></script>
        @endforeach
    @endif
    
    @if($alpineUrl = ink_config('scripts.alpinejs'))
        <script defer src="{{ ink_cached_script($alpineUrl) }}"></script>
    @endif
    
    @if(!empty($seoStructuredData))
        {!! $seoStructuredData !!}
    @endif
</head>
<body id="lara-ink-root">
    <x-layout.header />
    
    <main class="container mx-auto px-4 py-8">
        @yield('page')
    </main>
    
    <x-layout.footer />
    
    <script src="{{ $vite->getAssetUrl('app.js') }}"></script>
    <script src="/build/lara-ink-lang.js"></script>
    
    @if(!empty($pageJs))
        <script>{!! $pageJs !!}</script>
    @endif
</body>
</html>
```
