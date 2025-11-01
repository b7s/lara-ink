# 🎨 LaraInk

**A powerful DSL compiler that transforms Blade-like files into an independent SPA with Alpine.js, communicating with Laravel via REST API using Bearer Token authentication.**

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.3-blue)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-%3E%3D11.0-red)](https://laravel.com)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

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

---

## 📦 Installation

```bash
composer require b7s/lara-ink
```

The package will automatically:
- Create required directories (`resources/lara-ink/`, `public/build/`, etc.)
- Publish configuration file to `config/lara-ink.php`
- Set up default layout

---

## 🚀 Quick Start

### 1. Create Your First Page

Create `resources/lara-ink/pages/welcome.php`:

```php
<?php
ink_make()
    ->title('Welcome')
    ->layout('app');
?>

<div x-data="{ message: 'Hello from LaraInk!' }">
    <h1 x-text="message"></h1>
    
    @if($isLoggedIn)
        <p>Welcome back, {{ $user->name }}!</p>
    @else
        <p>Please log in to continue.</p>
    @endif
</div>
```

### 2. Build Your SPA

```bash
php artisan lara-ink:build
```

### 3. Access Your App

Open `http://your-app.test/index.html` in your browser!

---

## 📖 Documentation

- [Getting Started](docs/getting-started.md)
- [Page Configuration](docs/page-configuration.md)
- [Blade Directives](docs/blade-directives.md)
- [Routing & Navigation](docs/routing.md)
- [Authentication](docs/authentication.md)
- [API Integration](docs/api-integration.md)
- [Caching](docs/caching.md)
- [Deployment](docs/deployment.md)

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

---

## 🔧 Configuration

Edit `config/lara-ink.php`:

```php
return [
    'api_base_url' => env('LARAINK_API_URL', null),
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
