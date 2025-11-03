<?php

declare(strict_types=1);

use B7s\LaraInk\Services\ExternalScriptCacheService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new ExternalScriptCacheService();
    $this->cacheDir = ink_project_path(ink_config('output.build_dir', 'public/lara-ink/build') . '/cached-scripts');
});

afterEach(function () {
    // Clean up cache directory after each test
    if (File::exists($this->cacheDir)) {
        File::deleteDirectory($this->cacheDir);
    }
});

describe('ExternalScriptCacheService', function () {
    it('downloads and caches external script', function () {
        $url = 'https://cdn.example.com/script.js';
        $scriptContent = '// Test script content';

        Http::fake([
            $url => Http::response($scriptContent, 200),
        ]);

        $cachedUrl = $this->service->getCachedScriptUrl($url);

        expect($cachedUrl)
            ->toBeString()
            ->toContain('cached-scripts')
            ->toContain('.js');

        // Verify file was created
        $urlHash = md5($url);
        $cachedFilePath = $this->cacheDir . DIRECTORY_SEPARATOR . "external-{$urlHash}.js";

        expect(File::exists($cachedFilePath))->toBeTrue();
        expect(File::get($cachedFilePath))->toBe($scriptContent);
    });

    it('uses cached script when URL has not changed', function () {
        $url = 'https://cdn.example.com/script.js';
        $scriptContent = '// Test script content';

        Http::fake([
            $url => Http::response($scriptContent, 200),
        ]);

        // First call - downloads and caches
        $firstUrl = $this->service->getCachedScriptUrl($url);

        // Second call - should use cache without making HTTP request
        Http::fake(); // Reset fake, no responses configured
        $secondUrl = $this->service->getCachedScriptUrl($url);

        expect($firstUrl)->toBe($secondUrl);
    });

    it('re-downloads script when URL changes', function () {
        $url1 = 'https://cdn.example.com/script-v1.js';
        $url2 = 'https://cdn.example.com/script-v2.js';
        $content1 = '// Version 1';
        $content2 = '// Version 2';

        Http::fake([
            $url1 => Http::response($content1, 200),
            $url2 => Http::response($content2, 200),
        ]);

        $cachedUrl1 = $this->service->getCachedScriptUrl($url1);
        $cachedUrl2 = $this->service->getCachedScriptUrl($url2);

        expect($cachedUrl1)->not->toBe($cachedUrl2);
    });

    it('uses cached version when download fails but cache exists', function () {
        $url = 'https://cdn.example.com/script.js';
        $scriptContent = '// Cached content';

        // First request succeeds
        Http::fake([
            $url => Http::response($scriptContent, 200),
        ]);

        $firstUrl = $this->service->getCachedScriptUrl($url);

        // Second request fails, but should use cache
        Http::fake([
            $url => Http::response('', 500),
        ]);

        $secondUrl = $this->service->getCachedScriptUrl($url);

        expect($secondUrl)->toBe($firstUrl);
    });

    it('returns empty string for empty URL', function () {
        $result = $this->service->getCachedScriptUrl('');

        expect($result)->toBe('');
    });

    it('handles different file extensions', function () {
        $urls = [
            'https://cdn.example.com/script.js' => '.js',
            'https://cdn.example.com/script.min.js' => '.js',
            'https://cdn.example.com/module.mjs' => '.mjs',
        ];

        Http::fake([
            '*' => Http::response('// content', 200),
        ]);

        foreach ($urls as $url => $expectedExt) {
            $cachedUrl = $this->service->getCachedScriptUrl($url);
            expect($cachedUrl)->toContain($expectedExt);
        }
    });

    it('clears all cached scripts', function () {
        $url = 'https://cdn.example.com/script.js';

        Http::fake([
            $url => Http::response('// content', 200),
        ]);

        $this->service->getCachedScriptUrl($url);

        expect(File::exists($this->cacheDir))->toBeTrue();

        $this->service->clearCache();

        $files = File::files($this->cacheDir);
        expect($files)->toBeEmpty();
    });

    it('provides cache statistics', function () {
        $url1 = 'https://cdn.example.com/script1.js';
        $url2 = 'https://cdn.example.com/script2.js';

        Http::fake([
            '*' => Http::response('// content', 200),
        ]);

        $this->service->getCachedScriptUrl($url1);
        $this->service->getCachedScriptUrl($url2);

        $stats = $this->service->getCacheStats();

        expect($stats)
            ->toHaveKey('total_files')
            ->toHaveKey('total_size')
            ->toHaveKey('total_size_formatted')
            ->toHaveKey('scripts');

        expect($stats['total_files'])->toBe(2);
        expect($stats['scripts'])->toHaveCount(2);
    });

    it('formats bytes correctly', function () {
        $url = 'https://cdn.example.com/large-script.js';
        $contentLength = 1024 * 10; // 10 KB

        File::ensureDirectoryExists($this->cacheDir);

        $hash = md5($url);
        $cachedFilePath = $this->cacheDir . DIRECTORY_SEPARATOR . "external-{$hash}.js";
        $metaPath = $this->cacheDir . DIRECTORY_SEPARATOR . "external-{$hash}.meta.json";

        File::put($cachedFilePath, str_repeat('x', $contentLength));

        $metaPayload = [
            'url' => $url,
            'cached_at' => date(DATE_ATOM),
            'size' => $contentLength,
        ];

        File::put($metaPath, json_encode($metaPayload, JSON_PRETTY_PRINT));

        $stats = $this->service->getCacheStats();

        expect($stats['total_size'])->toBe($contentLength);

        $formatBytes = static function (int $bytes): string {
            $units = ['B', 'KB', 'MB', 'GB'];
            $bytes = max($bytes, 0);
            $pow = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
            $pow = min($pow, count($units) - 1);
            $value = $bytes / (1 << (10 * $pow));

            return round($value, 2) . ' ' . $units[$pow];
        };

        expect($stats['total_size_formatted'])->toBe($formatBytes($contentLength));
    });
});
