# Blade Directives

LaraInk supports most Laravel Blade directives and automatically compiles them to Alpine.js syntax for frontend reactivity.

## Echo Statements

### Standard Echo `{{ }}`

Outputs escaped content.

```php
<h1>{{ $title }}</h1>
```

Compiles to:

```html
<h1><span x-text="title"></span></h1>
```

### Raw Echo `{!! !!}`

Outputs unescaped HTML content.

```php
<div>{!! $htmlContent !!}</div>
```

Compiles to:

```html
<div><span x-html="htmlContent"></span></div>
```

## Conditional Directives

### @if / @elseif / @else / @endif

```php
@if($isLoggedIn)
    <p>Welcome back!</p>
@elseif($isGuest)
    <p>Hello, guest!</p>
@else
    <p>Please log in.</p>
@endif
```

Compiles to:

```html
<template x-if="isLoggedIn">
    <p>Welcome back!</p>
</template>
<template x-if="isGuest">
    <p>Hello, guest!</p>
</template>
<template x-if="true">
    <p>Please log in.</p>
</template>
```

### @unless / @endunless

Inverse of `@if`.

```php
@unless($hasAccess)
    <p>Access denied.</p>
@endunless
```

Compiles to:

```html
<template x-if="!hasAccess">
    <p>Access denied.</p>
</template>
```

### @isset / @endisset

Checks if variable is defined.

```php
@isset($user)
    <p>User: {{ $user->name }}</p>
@endisset
```

Compiles to:

```html
<template x-if="typeof user !== 'undefined'">
    <p>User: <span x-text="user.name"></span></p>
</template>
```

### @empty / @endempty

Checks if variable is empty.

```php
@empty($items)
    <p>No items found.</p>
@endempty
```

Compiles to:

```html
<template x-if="!items || (Array.isArray(items) && items.length === 0)">
    <p>No items found.</p>
</template>
```

## Loop Directives

### @foreach / @endforeach

Iterate over arrays.

```php
@foreach($users as $user)
    <div>{{ $user->name }}</div>
@endforeach
```

Compiles to:

```html
<template x-for="user in users">
    <div><span x-text="user.name"></span></div>
</template>
```

**With keys:**

```php
@foreach($items as $key => $value)
    <div>{{ $key }}: {{ $value }}</div>
@endforeach
```

Compiles to:

```html
<template x-for="(value, key) in items">
    <div><span x-text="key"></span>: <span x-text="value"></span></div>
</template>
```

### @for / @endfor

Standard for loop.

```php
@for($i = 0; $i < 10; $i++)
    <div>Item {{ $i }}</div>
@endfor
```

### @while / @endwhile

While loop.

```php
@while($condition)
    <div>Content</div>
@endwhile
```

## Switch Statements

### @switch / @case / @break / @default / @endswitch

```php
@switch($status)
    @case('active')
        <span class="badge-green">Active</span>
        @break
    
    @case('pending')
        <span class="badge-yellow">Pending</span>
        @break
    
    @default
        <span class="badge-gray">Unknown</span>
@endswitch
```

Compiles to:

```html
<div x-data="{ switchVar: status }">
    <template x-if="switchVar === 'active'">
        <span class="badge-green">Active</span>
    </template>
    <template x-if="switchVar === 'pending'">
        <span class="badge-yellow">Pending</span>
    </template>
    <template x-if="true">
        <span class="badge-gray">Unknown</span>
    </template>
</div>
```

## Complete Example

```php
<?php
ink_make()
    ->title('User Dashboard')
    ->auth(true);
?>

<div x-data="dashboard()">
    <h1>Welcome, {{ $user->name }}!</h1>
    
    @if($user->isAdmin)
        <div class="admin-panel">
            <h2>Admin Controls</h2>
        </div>
    @endif
    
    <div class="posts">
        <h2>Your Posts</h2>
        
        @empty($posts)
            <p>You haven't created any posts yet.</p>
        @else
            @foreach($posts as $post)
                <article>
                    <h3>{{ $post->title }}</h3>
                    <p>{{ $post->excerpt }}</p>
                    
                    @switch($post->status)
                        @case('published')
                            <span class="badge-success">Published</span>
                            @break
                        
                        @case('draft')
                            <span class="badge-warning">Draft</span>
                            @break
                        
                        @default
                            <span class="badge-secondary">Unknown</span>
                    @endswitch
                </article>
            @endforeach
        @endempty
    </div>
</div>

<script>
function dashboard() {
    return {
        user: {},
        posts: [],
        
        async init() {
            const response = await lara_ink.newReq('/api/dashboard');
            const data = await response.json();
            this.user = data.user;
            this.posts = data.posts;
        }
    }
}
</script>
```

## Unsupported Directives

The following Blade directives are **not supported** in LaraInk (as they are server-side only):

- `@php` / `@endphp` - PHP code blocks
- `@include` - Use layouts instead
- `@extends` / `@section` / `@yield` - Use layouts instead
- `@component` - Use Alpine.js components
- `@auth` / `@guest` - Use `ink_make()->auth(true)` instead
- `@can` / `@cannot` - Implement in API layer

## Best Practices

### 1. Keep Logic Simple

```php
// ❌ Complex logic in template
@if($user->role === 'admin' && $user->isActive && $user->hasPermission('edit'))
    <button>Edit</button>
@endif

// ✅ Compute in Alpine.js
<div x-data="{ canEdit: user.role === 'admin' && user.isActive && user.hasPermission('edit') }">
    <template x-if="canEdit">
        <button>Edit</button>
    </template>
</div>
```

### 2. Use Proper Data Types

```php
// ✅ Arrays for iteration
@foreach($items as $item)
    <div>{{ $item }}</div>
@endforeach

// ✅ Booleans for conditions
@if($isVisible)
    <div>Content</div>
@endif
```

### 3. Handle Empty States

```php
@empty($items)
    <p>No items to display.</p>
@else
    @foreach($items as $item)
        <div>{{ $item }}</div>
    @endforeach
@endempty
```

## Next Steps

- [Routing & Navigation](routing.md) - Learn about dynamic routing
- [API Integration](api-integration.md) - Connect to your backend
- [Authentication](authentication.md) - Secure your pages
