# LaraInk Documentation

Welcome to LaraInk! Transform your Laravel application into a standalone SPA with Alpine.js.

## Table of Contents

### Getting Started
- [Installation & Setup](./getting-started.md)
- [Quick Start Guide](./getting-started.md#your-first-page)
- [Configuration](./getting-started.md#configuration)

### Core Concepts
- [Creating Pages](./pages.md)
  - Page Structure
  - Configuration Options
  - Variables & Data
  - Alpine.js Integration
  - Blade-like Directives
- [Components](./components.md)
  - Creating Components
  - Props & Slots
  - Nested Components
  - Lazy Loading
- [Layouts](./layouts.md)
  - Creating Layouts
  - Layout Variables
  - Multiple Layouts
- [Alpine.js Integration](./alpine.md)
  - Data Binding
  - Event Handling
  - Directives
  - Best Practices

### Development
- [Development Workflow](./development.md)
  - Build Command
  - Dev Command (Hot Reload)
  - Vite Integration
  - Debugging
- [Tailwind CSS Setup](./tailwind-css.md)

### Advanced
- [Authentication](./authentication.md)
- [API Integration](./api.md)
- [Translations](./translations.md)
- [SEO Optimization](./seo.md)
- [Performance](./performance.md)

### Reference
- [Configuration Reference](./config-reference.md)
- [Helper Functions](./helpers.md)
- [CLI Commands](./commands.md)

## Quick Links

### Installation

```bash
composer require b7s/lara-ink
```

### Create Your First Page

```php
<?php
ink_make()
    ->layout('app')
    ->title('Welcome');
?>

<div x-data="{ message: 'Hello, LaraInk!' }">
    <h1 x-text="message"></h1>
</div>
```

### Build

```bash
php artisan lara-ink:build
```

### Development Mode

```bash
php artisan lara-ink:dev
```

## Features

- ✅ **Laravel Integration**: Seamless integration with Laravel
- ✅ **Alpine.js**: Reactive UI without heavy frameworks
- ✅ **Blade-like Syntax**: Familiar syntax that compiles to Alpine.js
- ✅ **Components**: Reusable UI components
- ✅ **Hot Reload**: Development mode with auto-rebuild
- ✅ **Vite Support**: Optional Vite integration for full HMR
- ✅ **Translations**: Built-in i18n support
- ✅ **SEO Friendly**: Meta tags and structured data
- ✅ **Lazy Loading**: Load components on demand
- ✅ **Minification**: Automatic HTML/CSS/JS minification
- ✅ **Caching**: Page-level caching support
- ✅ **Authentication**: Built-in auth with Bearer tokens

## Examples

### Simple Page

```php
<?php
ink_make()->title('Dashboard');
?>

<div x-data="{ stats: { users: 1250, revenue: 45000 } }">
    <h1>Dashboard</h1>
    <div>
        <p>Users: <span x-text="stats.users"></span></p>
        <p>Revenue: $<span x-text="stats.revenue"></span></p>
    </div>
</div>
```

### With Components

```php
<?php
ink_make()->title('Products');
$products = Product::all();
?>

<div class="grid grid-cols-3 gap-4">
    @foreach($products as $product)
        <x-product-card :product="$product" />
    @endforeach
</div>
```

### Interactive Form

```php
<?php
ink_make()->title('Contact');
?>

<div x-data="{ 
    form: { name: '', email: '', message: '' },
    submitted: false,
    async submit() {
        const response = await lara_ink.newReq('/contact', {
            method: 'POST',
            body: JSON.stringify(this.form)
        });
        this.submitted = true;
    }
}">
    <form @submit.prevent="submit" x-show="!submitted">
        <x-forms.input label="Name" x-model="form.name" />
        <x-forms.input label="Email" type="email" x-model="form.email" />
        <x-forms.textarea label="Message" x-model="form.message" />
        <x-ui.button type="submit">Send</x-ui.button>
    </form>
    
    <div x-show="submitted">
        <p>Thank you! We'll be in touch soon.</p>
    </div>
</div>
```

## Community & Support

- **Issues**: [GitHub Issues](https://github.com/b7s/lara-ink/issues)
- **Discussions**: [GitHub Discussions](https://github.com/b7s/lara-ink/discussions)
- **Documentation**: You're reading it!

## Contributing

Contributions are welcome! Please read our contributing guidelines.

## License

LaraInk is open-source software licensed under the MIT license.
