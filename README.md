<div style="text-align: center;" align="center">
    <img src="docs/art/logo.webp" width="256" alt="[LaraInk]">
    <h1>LaraInk</h1>
</div>

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.3-blue)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-%3E%3D11.0-red)](https://laravel.com)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

---

Blade + Alpine.js + Laravel = reactive SPAs in seconds.

A powerful DSL compiler that transforms Blade-like files into an independent SPA with [Alpine.js](https://alpinejs.dev), communicating with Laravel via REST API using Bearer Token authentication

---

## ✨ Features

- 🚀 **Blade-to-Alpine.js Compiler** - Write familiar Blade syntax, get reactive Alpine.js components
- 📦 **Independent SPA** - Deploy your frontend anywhere (CDN, Netlify, Vercel, S3)
- 🔐 **Bearer Token Auth** - Secure API communication with Laravel Sanctum
- ⚡ **Smart Caching** - Page-level caching with configurable TTL
- 🎯 **Dynamic Routing** - File-based routing with parameter support
- 🌍 **i18n Ready** - Built-in translation system
- 🎨 **Layout System** - Reusable layouts with nested folder support
- 📱 **SPA Router** - Client-side navigation with prefetching
- 🔧 **PHP Variables** - Define variables in PHP blocks, auto-converted to Alpine.js reactive data
- 🛡️ **Type Safety** - Automatic type detection and validation for variables (string, int, float, bool, array, Collection, Eloquent)
- 🧪 **With a lot of tests**

---

## 📦 Installation

```bash
composer require b7s/lara-ink
```

Run the install command:

```bash
php artisan lara-ink:install
```

The package will automatically:
- Create required directories (`resources/lara-ink/`, `public/build/`, etc.)
- Publish configuration file to `config/lara-ink.php`
- Set up default layout
- Set up Vite plugin to project root

---

## 🚀 Quick Start

### 1. Create Your First Page

Create `resources/lara-ink/pages/index.php`:

```php
<?php
ink_make()
    ->title(__('app.welcome'))
    ->layout('app')
    ->auth(true)
    ->middleware(['auth', 'role:admin'])
    ->cache(now()->addMinutes(10));

$users = User::all()->toArray();
/*
// It returns something like this:
[
    ['id' => 1, 'name' => 'John Doe'],
    ['id' => 2, 'name' => 'Max Mustermann'],
];*/
?>

<div>
    <h1>{{ __('app.welcome_message', ['name' => auth()->user()->name]) }}</h1>

    @foreach($users as $user)
        <p>
            <a href="{{ ink_route('see-user', $user['id']) }}">
                {{ $user['name'] }}
            </a>
        </p>
    @endforeach
</div>
```

### 2. Build Your SPA

```bash
php artisan lara-ink:build
```

### 3. Access Your App

Open `http://your-app.test/` in your browser!

If you are running inside Laravel, create a route to serve the index page:

```php
Route::get('/{path?}', function () {
    require ink_path('index.html');
})->where('path', '.*');
```

---

## 🔥 Hot Reload Development

LaraInk offers two ways to enable hot reload during development:

### Option 1: Native Dev Command (for quick start)

```bash
php artisan lara-ink:dev
```

This will:
- ✅ Watch for changes in `resources/lara-ink/**`
- ✅ Auto-rebuild when files change
- ✅ Show build status in terminal
- ✅ No Node.js required

### Option 2: Vite Integration (Recommended - Full Browser Hot Reload)

For automatic browser refresh, integrate with Vite:

**1. Install Vite (if not already installed):**

```bash
npm install -D vite laravel-vite-plugin
```

**2. The Vite plugin is automatically copied to your project root during `composer install`. Just import it in `vite.config.js`:**

```javascript
import { defineConfig } from 'vite';

// Add this lines if not already added
import laravel from 'laravel-vite-plugin';
import laraInkPlugin from './vite-plugin-lara-ink.js';

export default defineConfig({
    plugins: [
        // Add laravel plugin if not already added
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        
        // Add LaraInk Hot Reload - No configuration needed!
        laraInkPlugin(),
    ],
});
```

**3. Start Vite dev server:**

```bash
npm run dev
```

The plugin will:
- ✅ Build all pages on startup (if not already built)
- ✅ Watch for changes in `resources/lara-ink/`
- ✅ Rebuild only affected pages when you save
- ✅ Automatically reload your browser

**Smart Compilation:**
- **Page changed** → Rebuilds only that page
- **Layout changed** → Rebuilds all pages using that layout
- **Component changed** → Rebuilds all pages using that component

**Custom Configuration (Optional):**

```javascript
laraInkPlugin({
    watchPaths: ['resources/lara-ink/**'],
    buildCommand: 'php artisan lara-ink:build',
    debounce: 1000  // milliseconds
})
```

---

## 📖 Documentation

- [Overview](docs/README.md)
- [Getting Started](docs/getting-started.md)
- [Pages](docs/pages.md)
- [Components](docs/components.md)
- [Layouts](docs/layouts.md)
- [Alpine.js Integration](docs/alpine.md)
- [Middleware](docs/middleware.md)
- [Development Workflow](docs/development.md)
- [Tailwind CSS](docs/tailwind-css.md)
- [Security Hardening](docs/security-hardening.md)

---

## 🎯 Example: Dynamic Page with API

```php
<?php
ink_make()
    ->title('User Profile')
    ->auth(true)
    ->cache(600);
?>

<div x-data="profile()">
    <h1>Profile: <span x-text="user.name"></span></h1>
    
    @foreach($posts as $post)
        <article>
            <h2>{{ $post->title }}</h2>
            <p>{{ $post->excerpt }}</p>
        </article>
    @endforeach
    
    <button @click="loadMore()">Load More</button>
</div>

<script>
function profile() {
    return {
        user: {},
        posts: [],
        
        async init() {
            const response = await lara_ink.newReq('/api/profile');
            const data = await response.json();
            this.user = data.user;
            this.posts = data.posts;
        },
        
        async loadMore() {
            const response = await lara_ink.newReq('/api/posts?page=2');
            const data = await response.json();
            this.posts.push(...data.posts);
        }
    }
}
</script>
```

### 🔐 Example: Protected Page with Middleware

```php
<?php
ink_make()
    ->title('Admin Dashboard')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->cache(false);
?>

<div x-data="adminDashboard()">
    <h1>Admin Dashboard</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Users</h3>
            <p x-text="stats.users"></p>
        </div>
        <div class="stat-card">
            <h3>Active Sessions</h3>
            <p x-text="stats.sessions"></p>
        </div>
    </div>
</div>

<script>
function adminDashboard() {
    return {
        stats: {},
        
        async init() {
            const response = await lara_ink.newReq('/api/admin/stats');
            const data = await response.json();
            this.stats = data;
        }
    }
}
</script>
```

---

## 🔧 Configuration

Edit `config/lara-ink.php`:

---

## 🧪 Testing

```bash
# Run all tests
composer test

# Run specific test group
./vendor/bin/pest --group=unit
```

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

---

## 🙏 Credits

- **Author**: Bruno Tenorio
- **Email**: b7s@outlook.com
- Built with ❤️ using [Laravel](https://laravel.com) and [Alpine.js](https://alpinejs.dev)

---

## 🔗 Links

- [Documentation](docs/)
- [Issues](https://github.com/b7s/lara-ink/issues)
- [Changelog](CHANGELOG.md)
