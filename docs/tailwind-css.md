# Tailwind CSS Integration

LaraInk supports Tailwind CSS 4 out of the box. The package **does not** force Tailwind on your project - it's completely optional and user-configured.

## Installation

### 1. Install Dependencies

```bash
npm install -D tailwindcss @tailwindcss/vite vite
```

### 2. Configure Vite

Create or update your `vite.config.js` in the project root:

```javascript
import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    publicDir: false,
    build: {
        outDir: 'public/lara-ink/build', // Match your lara-ink.php config
        rollupOptions: {
            input: {
                app: 'resources/lara-ink/assets/app.js',
            },
            output: {
                entryFileNames: 'app-[hash].js',
                chunkFileNames: 'chunks/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash][extname]',
            },
        },
    },
    plugins: [
        tailwindcss(),
    ],
});
```

### 3. Import Tailwind in CSS

Add the import directive and source paths at the top of `resources/lara-ink/assets/app.css`:

```css
@import "tailwindcss";

/* Tailwind will scan these paths for classes */
@source "../../lara-ink/pages/**/*.php";
@source "../../lara-ink/layouts/**/*.php";

/* Your custom CSS */
```

The `@source` directives tell Tailwind 4 which files to scan for class usage. This is required for Tailwind to generate only the CSS you actually use.

### 4. Build Assets

```bash
php artisan lara-ink:build
```

## Usage in Pages

Once configured, you can use Tailwind classes in your LaraInk pages:

```php
<?php
ink_make()
    ->title('My Page')
    ->layout('app');
?>

<div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold text-gray-900">
        Hello Tailwind!
    </h1>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-8">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold mb-2">Card 1</h2>
            <p class="text-gray-600">Content goes here</p>
        </div>
        
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold mb-2">Card 2</h2>
            <p class="text-gray-600">Content goes here</p>
        </div>
    </div>
</div>
```

## Important Notes

- **LaraInk does NOT include Tailwind by default** - it's your choice to add it
- The `vite.config.js` is **never overwritten** if it already exists
- You have full control over your Vite configuration
- The default `app.css` includes helpful comments about Tailwind setup

## Troubleshooting

### Styles not applying?

1. Check that `@import "tailwindcss";` is at the top of your CSS file
2. Verify the Tailwind plugin is in your `vite.config.js`
3. Rebuild: `php artisan lara-ink:build`
4. Clear browser cache

### Build errors?

Make sure all dependencies are installed:
```bash
npm install
```

### CSS file is empty?

Check that your `app.js` imports the CSS:
```javascript
import './app.css';
```

This is automatically added by LaraInk when creating the default `app.js`.
