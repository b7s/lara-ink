# Alpine.js Integration

LaraInk uses Alpine.js as its reactive framework. All pages are compiled to work seamlessly with Alpine.js.

## What is Alpine.js?

Alpine.js is a lightweight JavaScript framework that provides reactive and declarative behavior directly in your HTML markup.

## Basic Concepts

### x-data

Define reactive data:

```php
<div x-data="{ count: 0, message: 'Hello' }">
    <p x-text="message"></p>
    <p x-text="count"></p>
</div>
```

### x-text

Display text content:

```php
<div x-data="{ name: 'John' }">
    <p x-text="name"></p>
    <!-- Renders: <p>John</p> -->
</div>
```

### x-html

Display HTML content:

```php
<div x-data="{ content: '<strong>Bold</strong>' }">
    <p x-html="content"></p>
    <!-- Renders: <p><strong>Bold</strong></p> -->
</div>
```

### x-show

Toggle visibility:

```php
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <p x-show="open">This is visible when open is true</p>
</div>
```

### x-if

Conditional rendering (removes from DOM):

```php
<div x-data="{ loggedIn: false }">
    <template x-if="loggedIn">
        <p>Welcome back!</p>
    </template>
    <template x-if="!loggedIn">
        <p>Please log in</p>
    </template>
</div>
```

### x-for

Loop through arrays:

```php
<div x-data="{ items: ['Apple', 'Banana', 'Orange'] }">
    <ul>
        <template x-for="item in items">
            <li x-text="item"></li>
        </template>
    </ul>
</div>
```

### @click (x-on:click)

Handle click events:

```php
<div x-data="{ count: 0 }">
    <button @click="count++">Increment</button>
    <button @click="count--">Decrement</button>
    <p x-text="count"></p>
</div>
```

### x-model

Two-way data binding:

```php
<div x-data="{ name: '' }">
    <input type="text" x-model="name">
    <p>Hello, <span x-text="name"></span>!</p>
</div>
```

### x-bind (or :)

Bind attributes:

```php
<div x-data="{ color: 'blue', disabled: false }">
    <button 
        :class="'bg-' + color + '-500'"
        :disabled="disabled"
    >
        Button
    </button>
</div>
```

## Event Handling

### Click Events

```php
<div x-data="{ message: 'Hello' }">
    <button @click="message = 'Clicked!'">
        Click me
    </button>
    <p x-text="message"></p>
</div>
```

### Input Events

```php
<div x-data="{ search: '' }">
    <input 
        type="text" 
        @input="search = $event.target.value"
        placeholder="Search..."
    >
    <p>Searching for: <span x-text="search"></span></p>
</div>
```

### Form Submit

```php
<div x-data="{ 
    email: '',
    async handleSubmit() {
        const response = await fetch('/api/subscribe', {
            method: 'POST',
            body: JSON.stringify({ email: this.email })
        });
    }
}">
    <form @submit.prevent="handleSubmit">
        <input type="email" x-model="email">
        <button type="submit">Subscribe</button>
    </form>
</div>
```

### Custom Events

```php
<div 
    x-data="{ count: 0 }"
    @increment.window="count++"
>
    <button @click="$dispatch('increment')">
        Increment
    </button>
    <p x-text="count"></p>
</div>
```

## Advanced Features

### x-init

Run code when component initializes:

```php
<div x-data="{ message: '' }" x-init="message = 'Initialized!'">
    <p x-text="message"></p>
</div>
```

### x-effect

Run code when dependencies change:

```php
<div x-data="{ count: 0 }" x-effect="console.log('Count is:', count)">
    <button @click="count++">Increment</button>
</div>
```

### $refs

Reference elements:

```php
<div x-data="{}">
    <input x-ref="myInput" type="text">
    <button @click="$refs.myInput.focus()">
        Focus Input
    </button>
</div>
```

### $el

Reference current element:

```php
<div x-data="{}" @click="$el.classList.toggle('active')">
    Click to toggle active class
</div>
```

### $watch

Watch for changes:

```php
<div x-data="{ count: 0 }" x-init="$watch('count', value => console.log(value))">
    <button @click="count++">Increment</button>
</div>
```

### $nextTick

Wait for DOM update:

```php
<div x-data="{ show: false }">
    <button @click="show = true; $nextTick(() => $refs.input.focus())">
        Show Input
    </button>
    <input x-show="show" x-ref="input">
</div>
```

## LaraInk Helpers

### lara_ink.trans()

Access translations:

```php
<div x-data="{}">
    <h1 x-text="lara_ink.trans('welcome.title')"></h1>
</div>
```

### lara_ink.newReq()

Make API requests:

```php
<div x-data="{
    users: [],
    async loadUsers() {
        const response = await lara_ink.newReq('/users', {
            method: 'GET'
        });
        this.users = await response.json();
    }
}" x-init="loadUsers()">
    <template x-for="user in users">
        <p x-text="user.name"></p>
    </template>
</div>
```

### lara_ink.is_authenticated()

Check authentication:

```php
<div x-data="{ 
    isAuth: false,
    async checkAuth() {
        this.isAuth = await lara_ink.is_authenticated();
    }
}" x-init="checkAuth()">
    <template x-if="isAuth">
        <p>You are logged in</p>
    </template>
</div>
```

## Plugins

### Intersect Plugin

Lazy load content when visible:

```php
<div x-data="{ loaded: false }" x-intersect="loaded = true">
    <template x-if="loaded">
        <img src="/large-image.jpg" alt="Lazy loaded">
    </template>
</div>
```

Configure in `config/lara-ink.php`:

```php
'scripts' => [
    'beforeAlpine' => [
        'https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.15.1/dist/cdn.min.js',
    ],
],
```

## Common Patterns

### Toggle Menu

```php
<div x-data="{ open: false }">
    <button @click="open = !open">Menu</button>
    <nav x-show="open" @click.away="open = false">
        <a href="/">Home</a>
        <a href="/about">About</a>
    </nav>
</div>
```

### Tabs

```php
<div x-data="{ tab: 'home' }">
    <nav>
        <button @click="tab = 'home'" :class="tab === 'home' && 'active'">
            Home
        </button>
        <button @click="tab = 'profile'" :class="tab === 'profile' && 'active'">
            Profile
        </button>
    </nav>
    
    <div x-show="tab === 'home'">Home content</div>
    <div x-show="tab === 'profile'">Profile content</div>
</div>
```

### Modal

```php
<div x-data="{ open: false }">
    <button @click="open = true">Open Modal</button>
    
    <div 
        x-show="open" 
        @click.self="open = false"
        class="modal-backdrop"
    >
        <div class="modal">
            <h2>Modal Title</h2>
            <p>Modal content</p>
            <button @click="open = false">Close</button>
        </div>
    </div>
</div>
```

### Search with Debounce

```php
<div x-data="{
    search: '',
    results: [],
    timeout: null,
    async performSearch() {
        clearTimeout(this.timeout);
        this.timeout = setTimeout(async () => {
            const response = await fetch('/api/search?q=' + this.search);
            this.results = await response.json();
        }, 300);
    }
}">
    <input 
        type="text" 
        x-model="search"
        @input="performSearch"
        placeholder="Search..."
    >
    <template x-for="result in results">
        <p x-text="result.name"></p>
    </template>
</div>
```

### Pagination

```php
<div x-data="{
    page: 1,
    perPage: 10,
    items: [...],
    get paginatedItems() {
        const start = (this.page - 1) * this.perPage;
        return this.items.slice(start, start + this.perPage);
    },
    get totalPages() {
        return Math.ceil(this.items.length / this.perPage);
    }
}">
    <template x-for="item in paginatedItems">
        <p x-text="item"></p>
    </template>
    
    <button @click="page--" :disabled="page === 1">Previous</button>
    <span x-text="page"></span> / <span x-text="totalPages"></span>
    <button @click="page++" :disabled="page === totalPages">Next</button>
</div>
```

## Best Practices

1. **Keep data close**: Define `x-data` on the closest parent
2. **Use computed properties**: Use getters for derived values
3. **Avoid deep nesting**: Keep component hierarchy flat
4. **Use events**: Communicate between components with events
5. **Leverage plugins**: Use Alpine plugins for common patterns
6. **Optimize loops**: Use `x-for` with `:key` for performance
7. **Handle errors**: Always handle async errors gracefully

## Performance Tips

1. **Use x-show for frequent toggles**: Keeps element in DOM
2. **Use x-if for rare toggles**: Removes element from DOM
3. **Debounce input handlers**: Prevent excessive updates
4. **Lazy load images**: Use Intersect plugin
5. **Minimize watchers**: Use `$watch` sparingly
6. **Cache API responses**: Store results in component data

## Resources

- [Alpine.js Documentation](https://alpinejs.dev/)
- [Alpine.js Plugins](https://alpinejs.dev/plugins)
- [Alpine.js Examples](https://alpinejs.dev/examples)
