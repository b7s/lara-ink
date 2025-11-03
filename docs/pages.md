# Creating Pages

Pages are the core of your LaraInk application. Each page is a PHP file that combines configuration with HTML/Alpine.js content.

## Basic Page Structure

```php
<?php
ink_make()
    ->layout('app')
    ->title('My Page');
?>

<div>
    <h1>Page Content</h1>
</div>
```

## Page Configuration

### Available Methods

```php
<?php
ink_make()
    ->layout('app')              // Layout to use
    ->title('Page Title')        // Page title
    ->cache(600)                 // Cache for 10 minutes
    ->requiresAuth()             // Require authentication
    ->middleware('admin')        // Custom middleware
    ->seo([                      // SEO configuration
        'description' => 'Page description',
        'keywords' => 'keyword1, keyword2',
        'og:image' => '/image.jpg',
    ]);
?>
```

### Layout

Specify which layout to use:

```php
ink_make()->layout('app')      // Uses resources/lara-ink/layouts/app.php
ink_make()->layout('admin')    // Uses resources/lara-ink/layouts/admin.php
```

### Title

Set the page title:

```php
ink_make()->title('Dashboard')
```

### Cache

Enable page caching (in seconds):

```php
ink_make()->cache(300)  // Cache for 5 minutes
ink_make()->cache(3600) // Cache for 1 hour
```

### Authentication

Require user authentication:

```php
ink_make()->requiresAuth()
```

Users will be redirected to login if not authenticated.

### SEO Configuration

Add SEO meta tags:

```php
ink_make()->seo([
    'description' => 'A comprehensive guide to LaraInk',
    'keywords' => 'laravel, spa, alpine.js',
    'og:title' => 'LaraInk Guide',
    'og:description' => 'Learn how to use LaraInk',
    'og:image' => 'https://example.com/image.jpg',
    'og:type' => 'article',
    'twitter:card' => 'summary_large_image',
])
```

## Using Variables

Define and use PHP variables in your pages:

```php
<?php
ink_make()->title('Products');

$products = [
    ['name' => 'Product 1', 'price' => 99.99],
    ['name' => 'Product 2', 'price' => 149.99],
];
?>

<div x-data="{ products: <?= json_encode($products) ?> }">
    <template x-for="product in products">
        <div>
            <h3 x-text="product.name"></h3>
            <p x-text="'$' + product.price"></p>
        </div>
    </template>
</div>
```

## Alpine.js Integration

LaraInk compiles to Alpine.js for reactivity:

### Data Binding

```php
<div x-data="{ count: 0 }">
    <button @click="count++">Increment</button>
    <span x-text="count"></span>
</div>
```

### Conditionals

```php
<div x-data="{ show: true }">
    <button @click="show = !show">Toggle</button>
    <p x-show="show">This is visible</p>
</div>
```

### Loops

```php
<div x-data="{ items: ['Apple', 'Banana', 'Orange'] }">
    <template x-for="item in items">
        <li x-text="item"></li>
    </template>
</div>
```

## Blade-like Directives

LaraInk supports Blade-like syntax that compiles to Alpine.js:

### @if / @else / @endif

```php
<?php $isAdmin = true; ?>

@if($isAdmin)
    <p>Welcome, Admin!</p>
@else
    <p>Welcome, User!</p>
@endif
```

### @foreach / @endforeach

```php
<?php $users = ['Alice', 'Bob', 'Charlie']; ?>

<ul>
@foreach($users as $user)
    <li>{{ $user }}</li>
@endforeach
</ul>
```

### @for / @endfor

```php
@for($i = 0; $i < 5; $i++)
    <p>Item {{ $i }}</p>
@endfor
```

## Translations

Use Laravel translations in your pages:

```php
<h1>{{ __('welcome.title') }}</h1>
<p>{{ trans('welcome.message') }}</p>
```

Compiles to:

```html
<h1><span x-text="lara_ink.trans('welcome.title')">welcome.title</span></h1>
```

## Route Parameters

Access route parameters:

```php
<?php
ink_make()
    ->title('User Profile')
    ->params(['id']);
?>

<div x-data="{ userId: request().id }">
    <h1>User Profile</h1>
    <p>User ID: <span x-text="userId"></span></p>
</div>
```

## JavaScript in Pages

Add custom JavaScript:

```php
<?php
ink_make()->title('Interactive Page');
?>

<div x-data="pageData()">
    <button @click="handleClick">Click me</button>
</div>

<script>
function pageData() {
    return {
        handleClick() {
            alert('Button clicked!');
        }
    };
}
</script>
```

## Styles in Pages

Add scoped or global styles:

### Scoped Styles

```php
<div class="my-component">
    <h1>Styled Component</h1>
</div>

<style scoped>
.my-component {
    background: #f0f0f0;
    padding: 20px;
}
</style>
```

### Global Styles

```php
<style>
.global-class {
    color: blue;
}
</style>
```

## Nested Pages

Organize pages in subdirectories:

```
resources/lara-ink/pages/
├── index.php
├── about.php
└── admin/
    ├── dashboard.php
    └── users.php
```

Access:
- `/lara-ink/pages/index.html`
- `/lara-ink/pages/about.html`
- `/lara-ink/pages/admin/dashboard.html`
- `/lara-ink/pages/admin/users.html`

## Best Practices

1. **Keep pages focused**: Each page should have a single responsibility
2. **Use components**: Extract reusable parts into components
3. **Leverage caching**: Cache static or slow-changing pages
4. **Optimize variables**: Only pass necessary data to Alpine.js
5. **Use translations**: Make your app multilingual-ready
6. **Add SEO**: Always configure SEO for public pages

## Example: Complete Page

```php
<?php
ink_make()
    ->layout('app')
    ->title('Product Catalog')
    ->cache(600)
    ->seo([
        'description' => 'Browse our product catalog',
        'keywords' => 'products, shop, catalog',
    ]);

$categories = ['Electronics', 'Clothing', 'Books'];
$featured = [
    ['name' => 'Laptop', 'price' => 999],
    ['name' => 'Phone', 'price' => 699],
];
?>

<div x-data="{
    categories: <?= json_encode($categories) ?>,
    featured: <?= json_encode($featured) ?>,
    selectedCategory: null
}">
    <!-- Header -->
    <header class="mb-8">
        <h1 class="text-3xl font-bold">{{ __('catalog.title') }}</h1>
        <p class="text-gray-600">{{ __('catalog.subtitle') }}</p>
    </header>

    <!-- Categories -->
    <nav class="mb-6">
        <template x-for="category in categories">
            <button 
                @click="selectedCategory = category"
                :class="selectedCategory === category ? 'bg-blue-500 text-white' : 'bg-gray-200'"
                class="px-4 py-2 rounded mr-2"
                x-text="category"
            ></button>
        </template>
    </nav>

    <!-- Featured Products -->
    <section>
        <h2 class="text-2xl font-semibold mb-4">Featured Products</h2>
        <div class="grid grid-cols-3 gap-4">
            <template x-for="product in featured">
                <x-product-card :product="product" />
            </template>
        </div>
    </section>
</div>

<style scoped>
header {
    border-bottom: 2px solid #e5e7eb;
    padding-bottom: 1rem;
}
</style>
```
