# Development Workflow

LaraInk provides powerful development tools for rapid iteration and hot reload capabilities.

## Development Commands

### Build Command

Build your pages for production:

```bash
php artisan lara-ink:build
```

Output:
```
INFO  LaraInk - Building SPA bundle

  Preparing build pipeline...

✓ Build completed
  Build completed successfully
  3 page(s) compiled
```

### Dev Command (Hot Reload)

Start development server with auto-rebuild:

```bash
php artisan lara-ink:dev
```

Features:
- ✅ Initial build
- ✅ Watches `resources/lara-ink/**` for changes
- ✅ Auto-rebuild on file changes
- ✅ Debounced (1 second) to avoid multiple builds
- ✅ Terminal feedback with timestamps

Output:
```
INFO  LaraInk - Starting development server

✓ Initial build completed
  2 page(s) compiled

INFO  Watching for changes in resources/lara-ink/...
  Press Ctrl+C to stop

  Changes detected, rebuilding...
  ✓ Rebuilt 2 page(s) at 15:23:45
```

## Hot Reload with Vite

For full browser hot reload, integrate with Vite.

### Setup

The Vite plugin is automatically copied to your project root during installation.

**1. Install Vite (if not already installed):**

```bash
npm install -D vite laravel-vite-plugin
```

**2. Configure `vite.config.js`:**

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import laraInkPlugin from './vite-plugin-lara-ink.js';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        
        // LaraInk Hot Reload - No configuration needed!
        laraInkPlugin(),
    ],
});
```

**3. Start Vite dev server:**

```bash
npm run dev
```

### Custom Configuration

Override default settings if needed:

```javascript
laraInkPlugin({
    watchPaths: ['resources/lara-ink/**'],
    buildCommand: 'php artisan lara-ink:build',
    debounce: 1000  // milliseconds
})
```

### How It Works

1. Vite watches `resources/lara-ink/**`
2. On change, runs `php artisan lara-ink:build`
3. Triggers full page reload in browser
4. Shows build status in terminal

## File Watching

### What Triggers Rebuild

Changes to any file in:
- `resources/lara-ink/pages/**`
- `resources/lara-ink/layouts/**`
- `resources/lara-ink/components/**`
- `resources/lara-ink/assets/**`

### Debouncing

Both dev command and Vite plugin use debouncing to prevent multiple builds:
- **Dev command**: 1 second
- **Vite plugin**: Configurable (default 1 second)

This means if you save multiple files quickly, only one build will run.

## Build Process

### What Happens During Build

1. **Discovery**: Find all `.php` files in `resources/lara-ink/pages/`
2. **Parsing**: Extract configuration and content
3. **Component Processing**: Replace `<x-*>` tags with component code
4. **Blade Compilation**: Compile Blade directives to Alpine.js
5. **Variable Substitution**: Replace PHP variables
6. **Translation Processing**: Convert `__()` to Alpine.js
7. **Minification**: Minify HTML, CSS, and JavaScript
8. **Layout Application**: Wrap content in layout
9. **Asset Management**: Copy and cache external scripts
10. **Output**: Write compiled HTML to `public/lara-ink/pages/`

### Build Artifacts

After build, you'll have:

```
public/lara-ink/
├── pages/
│   ├── index.html
│   ├── about.html
│   └── admin/
│       └── dashboard.html
├── build/
│   ├── assets/
│   │   └── app-[hash].css
│   ├── vendor/
│   │   ├── alpinejs-[hash].js
│   │   └── intersect-[hash].js
│   └── cached-scripts/
│       └── external-[hash].js
└── lara-ink-lang.js
```

## Performance Optimization

### Caching

Enable page caching for better performance:

```php
<?php
ink_make()
    ->cache(600);  // Cache for 10 minutes
?>
```

### Lazy Components

Load heavy components only when visible:

```php
<x-heavy-chart lazy :data="$chartData" />
```

### Minification

All HTML, CSS, and JavaScript is automatically minified:

**Before:**
```html
<div class="container">
    <h1>Title</h1>
    <p>Content</p>
</div>
```

**After:**
```html
<div class="container"><h1>Title</h1><p>Content</p></div>
```

**JavaScript before:**
```javascript
function pageData() {
    return {
        count: 0,
        increment() {
            this.count++;
        }
    };
}
```

**JavaScript after:**
```javascript
function pageData(){return{count:0,increment(){this.count++;}}}
```

### Asset Caching

External scripts are cached with hash-based filenames:
- `alpinejs-abc123.js`
- `intersect-xyz789.js`

This ensures:
- Browser caching
- Version control
- No CDN dependencies in production

## Debugging

### Enable Debug Mode

Set in `.env`:

```env
APP_DEBUG=true
```

### Component Not Found

If a component isn't found, you'll see:

```html
<!-- Component not found. Tried: ['button', 'ui.button'] | Available: [card, alert, forms.input] | Path: /path/to/components -->
```

This shows:
- Names that were tried
- Available components
- Components directory path

### Build Errors

Build errors show in terminal:

```
✗ Build failed: Syntax error in page: welcome.php
```

Check:
1. PHP syntax in page files
2. Blade directive syntax
3. Component names and props

### Verbose Output

For detailed build information, check Laravel logs:

```bash
tail -f storage/logs/laravel.log
```

## Development Tips

### 1. Use Dev Command

Always use `php artisan lara-ink:dev` during development for instant feedback.

### 2. Organize Files

Keep related files together:

```
resources/lara-ink/
├── pages/
│   └── admin/
│       ├── dashboard.php
│       └── users.php
├── components/
│   └── admin/
│       ├── stat-card.php
│       └── user-table.php
└── layouts/
    └── admin.php
```

### 3. Component-First Development

Build reusable components first, then compose pages:

```php
<!-- Build components -->
<x-ui.button>
<x-ui.card>
<x-forms.input>

<!-- Compose pages -->
<x-ui.card>
    <x-forms.input label="Name" />
    <x-ui.button>Submit</x-ui.button>
</x-ui.card>
```

### 4. Test in Browser

Use browser dev tools to:
- Inspect Alpine.js data
- Debug event handlers
- Check network requests
- Monitor performance

### 5. Version Control

Add to `.gitignore`:

```gitignore
/public/lara-ink/pages/
/public/lara-ink/build/
/vite-plugin-lara-ink.js  # Optional, if you don't want to commit it
```

Keep in version control:
```
/resources/lara-ink/
/config/lara-ink.php
```

## Workflow Comparison

### Option 1: Native Dev Command

```bash
php artisan lara-ink:dev
```

**Pros:**
- No Node.js required
- Simple setup
- Fast rebuild
- Terminal feedback

**Cons:**
- Manual browser refresh
- No HMR (Hot Module Replacement)

**Best for:**
- Simple projects
- Backend developers
- Quick prototyping

### Option 2: Vite Integration

```bash
npm run dev
```

**Pros:**
- Auto browser reload
- Integrates with existing Vite setup
- Full dev server features
- Better DX for frontend work

**Cons:**
- Requires Node.js
- More complex setup
- Slightly slower (runs PHP command)

**Best for:**
- Frontend-heavy projects
- Teams using Vite
- Full-stack development

## CI/CD Integration

### GitHub Actions

```yaml
name: Build LaraInk

on: [push]

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      
      - name: Install Dependencies
        run: composer install
      
      - name: Build LaraInk
        run: php artisan lara-ink:build
      
      - name: Upload Artifacts
        uses: actions/upload-artifact@v2
        with:
          name: lara-ink-pages
          path: public/lara-ink/
```

### Deployment

After build, deploy the `public/lara-ink/` directory to your web server or CDN.
