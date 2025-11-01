<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

final class ViteService
{
    public function build(): bool
    {
        $this->ensureViteConfig();
        
        $result = Process::path(App::basePath())
            ->run('npx vite build');

        return $result->successful();
    }

    public function generateManifest(string $buildDir): void
    {
        $files = File::files($buildDir);
        $manifest = [];

        foreach ($files as $file) {
            $filename = $file->getFilename();
            
            if (preg_match('/^app-([a-f0-9]+)\.(js|css)$/', $filename, $matches)) {
                $type = $matches[2];
                $hash = $matches[1];
                
                $manifest["app.{$type}"] = [
                    'file' => $filename,
                    'hash' => $hash,
                ];
            }
        }

        File::put($buildDir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
    }

    private function ensureViteConfig(): void
    {
        $configPath = App::basePath('vite.config.js');

        if (File::exists($configPath)) {
            return;
        }

        $config = <<<JS
import { defineConfig } from 'vite';

export default defineConfig({
    build: {
        outDir: 'public/build',
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
});
JS;

        File::put($configPath, $config);
    }

    public function getAssetUrl(string $asset): string
    {
        $buildDir = ink_config('output.build_dir', 'public/build');
        $manifestPath = App::basePath($buildDir . '/manifest.json');

        if (!File::exists($manifestPath)) {
            return "/build/{$asset}";
        }

        $manifest = json_decode(File::get($manifestPath), true);

        return isset($manifest[$asset]) 
            ? "/build/{$manifest[$asset]['file']}" 
            : "/build/{$asset}";
    }
}
