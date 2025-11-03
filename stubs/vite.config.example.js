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
        
        // Or with custom options:
        // laraInkPlugin({
        //     watchPaths: ['resources/lara-ink/**'],
        //     buildCommand: 'php artisan lara-ink:build',
        //     debounce: 1000
        // })
    ],
});
