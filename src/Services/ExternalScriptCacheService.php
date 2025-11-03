<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

final class ExternalScriptCacheService
{
    private string $cacheDir;

    public function __construct()
    {
        $this->cacheDir = ink_project_path(ink_config('output.build_dir', 'public/lara-ink/build') . '/cached-scripts');
    }

    /**
     * Get cached script URL or download and cache it
     * 
     * @param string $url External script URL
     * @return string Local cached script URL
     */
    public function getCachedScriptUrl(string $url): string
    {
        if (empty($url)) {
            return '';
        }

        // Generate hash from URL
        $urlHash = md5($url);
        $extension = $this->getExtensionFromUrl($url);
        $cachedFileName = "external-{$urlHash}.{$extension}";
        $cachedFilePath = $this->cacheDir . DIRECTORY_SEPARATOR . $cachedFileName;
        $metaFilePath = $this->cacheDir . DIRECTORY_SEPARATOR . "external-{$urlHash}.meta.json";

        // Ensure cache directory exists
        File::ensureDirectoryExists($this->cacheDir);

        // Check if cached file exists and URL hasn't changed
        if (File::exists($cachedFilePath) && File::exists($metaFilePath)) {
            $meta = json_decode(File::get($metaFilePath), true);

            if ($meta && isset($meta['url']) && $meta['url'] === $url) {
                // Cache is valid, return cached URL
                return $this->getCachedScriptWebUrl($cachedFileName);
            }
        }

        // Download and cache the script
        try {
            $response = Http::timeout(30)->get($url);

            if ($response->successful()) {
                $content = $response->body();

                // Save script content
                File::put($cachedFilePath, $content);

                // Save metadata
                $meta = [
                    'url' => $url,
                    'cached_at' => now()->toIso8601String(),
                    'size' => strlen($content),
                ];
                File::put($metaFilePath, json_encode($meta, JSON_PRETTY_PRINT));

                return $this->getCachedScriptWebUrl($cachedFileName);
            }
        } catch (\Exception $e) {
            // If download fails and we have a cached version, use it
            if (File::exists($cachedFilePath)) {
                return $this->getCachedScriptWebUrl($cachedFileName);
            }

            // Otherwise, return original URL as fallback
            return $url;
        }

        // Fallback to original URL if caching fails
        return $url;
    }

    /**
     * Get extension from URL
     * 
     * @param string $url
     * @return string
     */
    private function getExtensionFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if ($path === null || $path === false) {
            return 'js';
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $extension !== '' ? $extension : 'js';
    }

    /**
     * Get web URL for cached script
     * 
     * @param string $fileName
     * @return string
     */
    private function getCachedScriptWebUrl(string $fileName): string
    {
        $buildPath = ink_asset_url('build/cached-scripts');

        return rtrim($buildPath, '/') . '/' . $fileName;
    }

    /**
     * Clear all cached scripts
     * 
     * @return void
     */
    public function clearCache(): void
    {
        if (File::exists($this->cacheDir)) {
            File::cleanDirectory($this->cacheDir);
        }
    }

    /**
     * Get cache statistics
     * 
     * @return array<string, mixed>
     */
    public function getCacheStats(): array
    {
        if (!File::exists($this->cacheDir)) {
            return [
                'total_files' => 0,
                'total_size' => 0,
                'scripts' => [],
            ];
        }

        $files = File::files($this->cacheDir);
        $scripts = [];
        $totalSize = 0;

        foreach ($files as $file) {
            if (str_ends_with($file->getFilename(), '.meta.json')) {
                $meta = json_decode(File::get($file->getPathname()), true);

                if ($meta) {
                    $scripts[] = [
                        'url' => $meta['url'] ?? 'unknown',
                        'cached_at' => $meta['cached_at'] ?? 'unknown',
                        'size' => $meta['size'] ?? 0,
                    ];

                    $totalSize += $meta['size'] ?? 0;
                }
            }
        }

        return [
            'total_files' => count($scripts),
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'scripts' => $scripts,
        ];
    }

    /**
     * Format bytes to human-readable format
     * 
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes !== 0 ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
