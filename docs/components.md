# Components

Components are reusable pieces of UI that can be used across your pages. LaraInk supports Laravel Blade-style component syntax.

## Creating Components

Create components in `resources/lara-ink/components/`:

```
resources/lara-ink/components/
├── button.php
├── card.php
└── forms/
    └── input.php
```

## Basic Component

**File:** `resources/lara-ink/components/button.php`

```php
<button class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
    {{ $slot }}
</button>
```

**Usage in page:**

```php
<x-button>Click Me</x-button>
```

## Component with Props

**File:** `resources/lara-ink/components/card.php`

```php
<div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-xl font-bold mb-2">{{ $props['title'] }}</h2>
    <p class="text-gray-600">{{ $props['description'] }}</p>
    
    <div class="mt-4">
        {{ $slot }}
    </div>
</div>
```

**Usage:**

```php
<x-card 
    :title="'My Card Title'" 
    :description="'This is a description'"
>
    <p>Card content goes here</p>
</x-card>
```

## Props

### Static Props

Pass static values:

```php
<x-button color="blue" size="large">
    Submit
</x-button>
```

Access in component:

```php
<button class="btn-{{ $props['color'] }} btn-{{ $props['size'] }}">
    {{ $slot }}
</button>
```

### Dynamic Props (Binding)

Use `:` prefix for dynamic values:

```php
<?php $userColor = 'red'; ?>

<x-button :color="$userColor">
    Dynamic Color
</x-button>
```

### Props with PHP Expressions

```php
<x-card :title="'User: ' . $userName" :count="count($items)">
    Content
</x-card>
```

## Nested Components

**File:** `resources/lara-ink/components/forms/input.php`

```php
<div class="mb-4">
    <label class="block text-sm font-medium mb-2">
        {{ $props['label'] }}
    </label>
    <input 
        type="{{ $props['type'] ?? 'text' }}"
        class="w-full px-3 py-2 border rounded"
        placeholder="{{ $props['placeholder'] ?? '' }}"
    />
</div>
```

**Usage:**

```php
<x-forms.input 
    label="Email" 
    type="email" 
    placeholder="your@email.com" 
/>
```

## Self-Closing Components

Components without content can be self-closing:

```php
<x-icon name="home" />
<x-divider />
<x-forms.input label="Name" />
```

## Slot Content

The `{{ $slot }}` variable contains the content between component tags:

```php
<!-- Component: alert.php -->
<div class="alert alert-{{ $props['type'] }}">
    {{ $slot }}
</div>

<!-- Usage -->
<x-alert type="success">
    Operation completed successfully!
</x-alert>
```

## Alpine.js in Components

Components can use Alpine.js directives:

**File:** `resources/lara-ink/components/counter.php`

```php
<div x-data="{ count: {{ $props['initial'] ?? 0 }} }">
    <button @click="count--">-</button>
    <span x-text="count"></span>
    <button @click="count++">+</button>
</div>
```

**Usage:**

```php
<x-counter :initial="10" />
```

## Lazy Loading Components

Use the `lazy` attribute to load components only when visible:

```php
<x-heavy-component lazy :data="$largeDataset" />
```

This will:
- Not render the component initially
- Load when it's about to enter viewport (50px margin)
- Reduce initial page size
- Improve performance

## Component Discovery

LaraInk automatically discovers all components in:
- `resources/lara-ink/components/`
- All subdirectories

Component naming:
- `button.php` → `<x-button>`
- `card.php` → `<x-card>`
- `forms/input.php` → `<x-forms.input>`
- `admin/table.php` → `<x-admin.table>`

## Advanced Example: Product Card

**File:** `resources/lara-ink/components/product-card.php`

```php
<div 
    class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition"
    x-data="{ 
        product: {{ json_encode($props['product']) }},
        quantity: 1 
    }"
>
    <!-- Image -->
    <img 
        :src="product.image" 
        :alt="product.name"
        class="w-full h-48 object-cover"
    />
    
    <!-- Content -->
    <div class="p-4">
        <h3 class="text-lg font-semibold mb-2" x-text="product.name"></h3>
        <p class="text-gray-600 text-sm mb-4" x-text="product.description"></p>
        
        <!-- Price -->
        <div class="flex items-center justify-between mb-4">
            <span class="text-2xl font-bold text-blue-600">
                $<span x-text="product.price"></span>
            </span>
            <span 
                x-show="product.discount" 
                class="text-sm text-red-500"
                x-text="'-' + product.discount + '%'"
            ></span>
        </div>
        
        <!-- Quantity -->
        <div class="flex items-center gap-2 mb-4">
            <button 
                @click="quantity = Math.max(1, quantity - 1)"
                class="px-3 py-1 bg-gray-200 rounded"
            >-</button>
            <span x-text="quantity" class="px-4"></span>
            <button 
                @click="quantity++"
                class="px-3 py-1 bg-gray-200 rounded"
            >+</button>
        </div>
        
        <!-- Add to Cart -->
        <button 
            @click="$dispatch('add-to-cart', { product, quantity })"
            class="w-full py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
        >
            Add to Cart
        </button>
    </div>
</div>
```

**Usage:**

```php
<?php
$product = [
    'id' => 1,
    'name' => 'Laptop Pro',
    'description' => 'High-performance laptop',
    'price' => 1299.99,
    'discount' => 10,
    'image' => '/images/laptop.jpg'
];
?>

<div x-data="{ cart: [] }" @add-to-cart.window="cart.push($event.detail)">
    <x-product-card :product="$product" />
    
    <!-- Cart Summary -->
    <div class="mt-4">
        <p>Items in cart: <span x-text="cart.length"></span></p>
    </div>
</div>
```

## Component with Multiple Slots (Future)

Currently, LaraInk supports a single `{{ $slot }}`. Named slots may be added in future versions.

## Best Practices

1. **Keep components small**: Each component should do one thing well
2. **Use props for customization**: Make components flexible with props
3. **Document props**: Add comments explaining required props
4. **Avoid deep nesting**: Keep component hierarchy shallow
5. **Use lazy loading**: For heavy components below the fold
6. **Test in isolation**: Ensure components work independently

## Component Library Example

Create a component library structure:

```
resources/lara-ink/components/
├── ui/
│   ├── button.php
│   ├── card.php
│   ├── badge.php
│   └── modal.php
├── forms/
│   ├── input.php
│   ├── select.php
│   ├── checkbox.php
│   └── textarea.php
├── layout/
│   ├── header.php
│   ├── footer.php
│   └── sidebar.php
└── icons/
    ├── home.php
    ├── user.php
    └── settings.php
```

Usage:

```php
<x-ui.button>Click</x-ui.button>
<x-forms.input label="Name" />
<x-layout.header />
<x-icons.home />
```

## Debugging Components

If a component is not found, LaraInk will show:

```html
<!-- Component not found. Tried: ['component-name', 'component.name'] | Available: [list] | Path: /path/to/components -->
```

This helps you:
- See what names were tried
- See available components
- Verify the components path
