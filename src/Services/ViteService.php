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
        File::ensureDirectoryExists($buildDir);

        $manifest = [];

        $jsFiles = glob(rtrim($buildDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'app-*.js');
        if (!empty($jsFiles) && is_file($jsFiles[0])) {
            $jsFile = basename($jsFiles[0]);
            $manifest['app.js'] = [
                'file' => $jsFile,
                'hash' => $this->extractHash($jsFile),
            ];
        }

        $cssFiles = glob(rtrim($buildDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'app-*.css');
        if (!empty($cssFiles) && is_file($cssFiles[0])) {
            $cssFile = 'assets/' . basename($cssFiles[0]);
            $manifest['app.css'] = [
                'file' => $cssFile,
                'hash' => $this->extractHash(basename($cssFiles[0])),
            ];
        }

        File::put(rtrim($buildDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function extractHash(string $filename): string
    {
        if (preg_match('/-([A-Za-z0-9]{6,})\./', $filename, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function ensureViteConfig(): void
    {
        $configPath = App::basePath('vite.config.js');

        // If user already has a vite.config.js, respect it and don't overwrite
        if (File::exists($configPath)) {
            return;
        }

        // Only create a default config if it doesn't exist
        $outDir = trim(ink_config('output.build_dir', 'public/build'), '/');

        if ($outDir === '') {
            $outDir = 'public/build';
        }

        $config = <<<JS
import { defineConfig } from 'vite';

export default defineConfig({
    publicDir: false,
    build: {
        cssMinify: true,
        jsMinify: true,
        outDir: '{$outDir}',
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
        $buildDir = ink_project_path(ink_config('output.build_dir', 'public/build'));
        $manifestPath = $buildDir . DIRECTORY_SEPARATOR . 'manifest.json';

        if (!File::exists($manifestPath)) {
            // If manifest doesn't exist, return fallback
            return $this->getFallbackAssetUrl($asset);
        }

        $manifest = json_decode(File::get($manifestPath), true);

        if (isset($manifest[$asset])) {
            $relativePath = $manifest[$asset]['file'] ?? $asset;
            return $this->toWebPath($buildDir . DIRECTORY_SEPARATOR . $relativePath);
        }

        return $this->toWebPath($buildDir . DIRECTORY_SEPARATOR . $asset);
    }

    private function getFallbackAssetUrl(string $asset): string
    {
        $configuredBuildDir = ink_config('output.build_dir', 'public/build');
        $absoluteDir = ink_project_path($configuredBuildDir);
        $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . ltrim($asset, DIRECTORY_SEPARATOR);

        if (File::exists($absolutePath)) {
            return $this->toWebPath($absolutePath);
        }

        $relativeDir = str_replace('\\', '/', trim($configuredBuildDir, '/'));

        if (str_starts_with($relativeDir, 'public/')) {
            $relativeDir = substr($relativeDir, strlen('public/')) ?: '';
        }

        $relativeAsset = trim($relativeDir . '/' . ltrim($asset, '/'), '/');

        return '/' . $relativeAsset;
    }

    private function toWebPath(string $absolutePath): string
    {
        $projectRoot = rtrim(ink_project_path(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $normalized = str_replace('\\', '/', $absolutePath);
        $projectNormalized = str_replace('\\', '/', $projectRoot);

        if (str_starts_with($normalized, $projectNormalized)) {
            $normalized = substr($normalized, strlen($projectNormalized));
        }

        $normalized = ltrim($normalized, '/');

        if (str_starts_with($normalized, 'public/')) {
            $normalized = substr($normalized, strlen('public/')) ?: '';
        }

        return '/' . ltrim($normalized, '/');
    }

    public function activeHotReload(): string
    {
        $viteHotUrl = '';

        $hotFile = public_path('hot');
        if (file_exists($hotFile)) {
            $viteHotUrl = rtrim(trim(file_get_contents($hotFile)), '/');
        }

        return $viteHotUrl ? '<script type="module" src="' . $viteHotUrl . '/@vite/client"></script>' : '';
    }
}
