<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class AssetManagerService
{
    /**
     * Prepare all script assets defined in configuration.
     *
     * @return array<int, string> List of script asset paths relative to the public directory (prefixed with /)
     */
    public function prepareScripts(): array
    {
        $scripts = [];

        // Process beforeAlpine scripts first
        $beforeAlpine = config('lara-ink.scripts.beforeAlpine', []);
        if (!is_array($beforeAlpine)) {
            throw new \RuntimeException('The configuration value "lara-ink.scripts.beforeAlpine" must be an array.');
        }

        foreach ($beforeAlpine as $index => $script) {
            if (!is_string($script) || trim($script) === '') {
                throw new \RuntimeException(sprintf('beforeAlpine script entry at index %d must be a non-empty string.', $index));
            }

            $scripts[] = $this->resolveScript($script, "beforeAlpine_{$index}");
        }

        // Then Alpine.js
        $alpineUrl = config('lara-ink.scripts.alpinejs');
        if (!is_string($alpineUrl) || trim($alpineUrl) === '') {
            throw new \RuntimeException('The configuration value "lara-ink.scripts.alpinejs" must be a non-empty string with the full url to the Alpine.js script.');
        }

        $scripts[] = $this->storeRemoteScript($alpineUrl, 'alpinejs');

        // Finally, other scripts
        $others = config('lara-ink.scripts.others', []);
        if (!is_array($others)) {
            throw new \RuntimeException('The configuration value "lara-ink.scripts.others" must be an array.');
        }

        foreach ($others as $index => $script) {
            if (!is_string($script) || trim($script) === '') {
                throw new \RuntimeException(sprintf('Script entry at index %d must be a non-empty string.', $index));
            }

            $scripts[] = $this->resolveScript($script, "others_{$index}");
        }

        return $scripts;
    }

    /**
     * Prepare additional stylesheet assets defined in configuration.
     *
     * @return array<int, string> List of stylesheet asset paths relative to the public directory (prefixed with /)
     */
    public function prepareStyles(): array
    {
        $styles = config('lara-ink.styles.others', []);
        if (!is_array($styles)) {
            throw new \RuntimeException('The configuration value "lara-ink.styles.others" must be an array.');
        }

        $prepared = [];

        foreach ($styles as $index => $style) {
            if (!is_string($style) || trim($style) === '') {
                throw new \RuntimeException(sprintf('Style entry at index %d must be a non-empty string.', $index));
            }

            $prepared[] = $this->resolveStyle($style, "style_{$index}");
        }

        return $prepared;
    }

    private function resolveScript(string $value, string $identifier): string
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $this->storeRemoteScript($value, $identifier);
        }

        $absolutePath = $this->resolveLocalPath($value);

        if (!File::exists($absolutePath)) {
            throw new \RuntimeException(sprintf('Script file not found at path: %s', $absolutePath));
        }

        return $this->copyToBuild($absolutePath, $value, $identifier, 'js');
    }

    private function resolveStyle(string $value, string $identifier): string
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $this->storeRemoteStyle($value, $identifier);
        }

        $absolutePath = $this->resolveLocalPath($value);

        if (!File::exists($absolutePath)) {
            throw new \RuntimeException(sprintf('Stylesheet not found at path: %s', $absolutePath));
        }

        return $this->copyToBuild($absolutePath, $value, $identifier, 'css');
    }

    private function storeRemoteScript(string $url, string $identifier): string
    {
        $cachedPath = $this->downloadRemoteAsset($url, 'scripts');
        return $this->copyToBuild($cachedPath, $url, $identifier, 'js');
    }

    private function storeRemoteStyle(string $url, string $identifier): string
    {
        $cachedPath = $this->downloadRemoteAsset($url, 'styles');
        return $this->copyToBuild($cachedPath, $url, $identifier, 'css');
    }

    private function downloadRemoteAsset(string $url, string $type): string
    {
        $parsedPath = parse_url($url, PHP_URL_PATH) ?: '';
        $extension = pathinfo($parsedPath, PATHINFO_EXTENSION);

        if ($extension === '') {
            $extension = $type === 'styles' ? 'css' : 'js';
        }

        $cacheDir = storage_path("app/lara-ink/cache/{$type}");
        File::ensureDirectoryExists($cacheDir);

        $hash = sha1($url);
        $cachedPath = $cacheDir . DIRECTORY_SEPARATOR . $hash . '.' . $extension;

        if (!File::exists($cachedPath)) {
            $response = Http::timeout(30)->get($url);

            if (!$response->ok()) {
                throw new \RuntimeException(sprintf('Failed to download asset from %s. HTTP status: %s', $url, $response->status()));
            }

            File::put($cachedPath, $response->body());
        }

        return $cachedPath;
    }

    private function copyToBuild(string $sourcePath, string $originalIdentifier, string $alias, string $defaultExtension): string
    {
        $buildDirConfig = ink_config('output.build_dir', 'public/build');
        $buildDirAbsolute = ink_project_path($buildDirConfig);
        $vendorDir = $buildDirAbsolute . DIRECTORY_SEPARATOR . 'vendor';

        File::ensureDirectoryExists($vendorDir);

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: $defaultExtension;
        $baseName = pathinfo($sourcePath, PATHINFO_FILENAME);

        if ($baseName === '') {
            $baseName = $alias;
        }

        $slug = Str::slug($baseName);
        if ($slug === '') {
            $slug = $alias;
        }

        $hashFragment = substr(sha1($originalIdentifier), 0, 8);
        $fileName = $slug . '-' . $hashFragment . '.' . $extension;

        $destinationPath = $vendorDir . DIRECTORY_SEPARATOR . $fileName;
        File::copy($sourcePath, $destinationPath);

        return $this->toWebPath($destinationPath);
    }

    private function resolveLocalPath(string $value): string
    {
        if ($this->isAbsolutePath($value)) {
            return $value;
        }

        return App::basePath($value);
    }

    private function isAbsolutePath(string $path): bool
    {
        if (Str::startsWith($path, ['/', '\\'])) {
            return true;
        }

        return (bool) preg_match('/^[A-Za-z]:[\\\/]/', $path);
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
}
