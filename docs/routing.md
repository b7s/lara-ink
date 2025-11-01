# Routing & Navigation

LaraInk uses file-based routing with support for dynamic parameters, similar to Next.js or Nuxt.js.

## File-Based Routing

### Basic Routes

File paths automatically become routes:

| File | Route |
|------|-------|
| `pages/index.php` | `/` |
| `pages/about.php` | `/about` |
| `pages/contact.php` | `/contact` |
| `pages/blog/index.php` | `/blog` |
| `pages/blog/post.php` | `/blog/post` |

### Dynamic Parameters

Use square brackets `[]` for dynamic segments:

| File | Route | Parameters |
|------|-------|------------|
| `pages/user/[id].php` | `/user/123` | `{ id: '123' }` |
| `pages/blog/[slug].php` | `/blog/my-post` | `{ slug: 'my-post' }` |
| `pages/product/[category]/[id].php` | `/product/electronics/42` | `{ category: 'electronics', id: '42' }` |

### Accessing Parameters

Parameters are available in your page via the `request()` object:

```php
<?php
ink_make()->title('User Profile');
?>

<div x-data="profile()">
    <h1>User ID: <span x-text="request().id"></span></h1>
</div>

<script>
function profile() {
    return {
        userId: request().id,
        
        async init() {
            const response = await lara_ink.newReq(`/api/users/${this.userId}`);
            const data = await response.json();
            // Handle user data
        }
    }
}
</script>
```

## Navigation

### Internal Links

Use standard `<a>` tags for internal navigation. LaraInk automatically intercepts clicks:

```php
<nav>
    <a href="/">Home</a>
    <a href="/about">About</a>
    <a href="/contact">Contact</a>
</nav>
```

### Dynamic Links

```php
@foreach($posts as $post)
    <a href="/blog/{{ $post->slug }}">{{ $post->title }}</a>
@endforeach
```

### Programmatic Navigation

Use Alpine.js to navigate programmatically:

```php
<button @click="window.history.pushState({}, '', '/dashboard'); window.dispatchEvent(new Event('popstate'))">
    Go to Dashboard
</button>
```

Or create a helper:

```php
<div x-data="{ 
    navigate(url) {
        window.history.pushState({}, '', url);
        window.dispatchEvent(new Event('popstate'));
    }
}">
    <button @click="navigate('/dashboard')">Dashboard</button>
</div>
```

## The `ink_route()` Helper

Use `ink_route()` to generate URLs for both LaraInk pages and Laravel routes.

### Basic Usage

```php
// LaraInk page
<a href="<?= ink_route('about')->url ?>">About</a>

// Laravel named route
<a href="<?= ink_route('api.users.show', ['id' => 123])->url ?>">User</a>
```

### With Parameters

```php
// Dynamic page route
<a href="<?= ink_route('blog/[slug]', ['slug' => 'my-post'])->url ?>">
    Read Post
</a>

// Multiple parameters
<a href="<?= ink_route('product/[category]/[id]', [
    'category' => 'electronics',
    'id' => 42
])->url ?>">
    View Product
</a>
```

### In Forms

```php
<?php $route = ink_route('api.posts.store', [], 'POST'); ?>

<form action="<?= $route->url ?>" method="<?= $route->method ?>">
    <input type="text" name="title">
    <button type="submit">Create Post</button>
</form>
```

### In JavaScript

```php
<script>
async function deletePost(id) {
    const route = <?= json_encode(ink_route('api.posts.destroy', ['id' => 'ID_PLACEHOLDER'], 'DELETE')) ?>;
    const url = route.url.replace('ID_PLACEHOLDER', id);
    
    const response = await lara_ink.newReq(url, {
        method: route.method
    });
    
    if (response.ok) {
        alert('Post deleted!');
    }
}
</script>
```

## Route Prefetching

LaraInk automatically prefetches pages when you hover over links:

```php
<!-- This page will be prefetched on hover -->
<a href="/about">About</a>
```

### Disable Prefetching

Add `data-no-prefetch` attribute:

```php
<a href="/external" data-no-prefetch>External Link</a>
```

## Complete Example

### File: `pages/blog/[slug].php`

```php
<?php
ink_make()
    ->title('Blog Post')
    ->cache(600);
?>

<div x-data="blogPost()">
    <article>
        <h1 x-text="post.title"></h1>
        <div x-html="post.content"></div>
        
        <div class="meta">
            <span x-text="post.author"></span>
            <span x-text="post.published_at"></span>
        </div>
        
        <div class="actions">
            <button @click="likePost()">Like</button>
            <button @click="sharePost()">Share</button>
        </div>
    </article>
    
    <nav class="pagination">
        <template x-if="post.previous">
            <a :href="`/blog/${post.previous.slug}`" x-text="post.previous.title"></a>
        </template>
        
        <template x-if="post.next">
            <a :href="`/blog/${post.next.slug}`" x-text="post.next.title"></a>
        </template>
    </nav>
</div>

<script>
function blogPost() {
    return {
        post: {},
        
        async init() {
            const slug = request().slug;
            const response = await lara_ink.newReq(`/api/blog/${slug}`);
            
            if (!response.ok) {
                window.location.href = '/404';
                return;
            }
            
            this.post = await response.json();
        },
        
        async likePost() {
            const response = await lara_ink.newReq(`/api/blog/${this.post.slug}/like`, {
                method: 'POST'
            });
            
            if (response.ok) {
                this.post.likes++;
            }
        },
        
        sharePost() {
            if (navigator.share) {
                navigator.share({
                    title: this.post.title,
                    url: window.location.href
                });
            }
        }
    }
}
</script>
```

## Best Practices

### 1. Use Semantic URLs

```php
// ❌ Bad
pages/p/[id].php  // /p/123

// ✅ Good
pages/post/[slug].php  // /post/my-awesome-post
```

### 2. Handle 404s

```php
async init() {
    const response = await lara_ink.newReq(`/api/resource/${this.id}`);
    
    if (response.status === 404) {
        window.location.href = '/404';
        return;
    }
    
    this.data = await response.json();
}
```

### 3. Validate Parameters

```php
async init() {
    const id = parseInt(request().id);
    
    if (isNaN(id) || id <= 0) {
        window.location.href = '/404';
        return;
    }
    
    // Proceed with valid ID
}
```

### 4. Use Loading States

```php
<div x-data="{ loading: true, data: null }">
    <template x-if="loading">
        <div>Loading...</div>
    </template>
    
    <template x-if="!loading && data">
        <div x-text="data.title"></div>
    </template>
</div>
```

## Next Steps

- [Authentication](authentication.md) - Secure your routes
- [API Integration](api-integration.md) - Connect to Laravel backend
- [Caching](caching.md) - Optimize performance
