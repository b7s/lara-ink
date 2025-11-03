<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

use B7s\LaraInk\DTOs\RouteInfo;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

final class RouteService
{
    /**
     * @var array<string, array{pattern: string, params: array<string>}>
     */
    private array $pageRoutes = [];

    private bool $pagesInitialized = false;

    /**
     * @param array<string> $params
     */
    public function registerPageRoute(string $slug, string $pattern, array $params): void
    {
        $normalizedSlug = $this->normalizeSlug($slug);
        $normalizedPattern = $this->normalizeSlug($pattern);

        $this->pageRoutes[$normalizedSlug] = [
            'pattern' => $normalizedPattern,
            'params' => $params,
            'type' => 'lara-ink',
        ];
    }

    /**
     * @param array<string, mixed> $params
     */
    public function resolve(string $nameOrSlug, array $params = [], ?string $method = null): RouteInfo
    {
        $this->ensureDefaultPagesRegistered();

        if ($this->hasPageRoute($nameOrSlug)) {
            $url = $this->buildPageUrl($nameOrSlug, $params);
            $route = $this->pageRoutes[$this->normalizeSlug($nameOrSlug)] ?? null;
            $type = $route['type'] ?? 'lara-ink';

            return new RouteInfo($url, $method ?? 'GET', $type);
        }

        $normalizedSlug = $this->normalizeSlug($nameOrSlug);

        if ($normalizedSlug !== $nameOrSlug && $this->hasPageRoute($normalizedSlug)) {
            $url = $this->buildPageUrl($normalizedSlug, $params);
            $route = $this->pageRoutes[$normalizedSlug] ?? null;
            $type = $route['type'] ?? 'lara-ink';

            return new RouteInfo($url, $method ?? 'GET', $type);
        }

        if (Route::has($nameOrSlug)) {
            $url = route($nameOrSlug, $params);
            $route = Route::getRoutes()->getByName($nameOrSlug);
            $routeMethod = $method ?? ($route ? $this->getRouteMethod($route) : 'GET');

            return new RouteInfo($url, $routeMethod, 'laravel');
        }

        throw new \RuntimeException("Route or page not found: {$nameOrSlug}");
    }

    /**
     * @param array<string, mixed> $params
     */
    private function buildPageUrl(string $slug, array $params): string
    {
        $normalizedSlug = $this->normalizeSlug($slug);

        $route = $this->pageRoutes[$normalizedSlug] ?? null;

        $url = $normalizedSlug;

        foreach (($route['params'] ?? []) as $param) {
            $value = $params[$param] ?? '';
            $url = preg_replace('/\[' . preg_quote($param, '/') . '\]/', (string) $value, $url);
        }

        return $url;
    }

    private function hasPageRoute(string $slug): bool
    {
        return isset($this->pageRoutes[$this->normalizeSlug($slug)]);
    }

    private function normalizeSlug(string $slug): string
    {
        $slug = trim($slug);

        if ($slug === '') {
            return '/index';
        }

        if (!str_starts_with($slug, '/')) {
            $slug = '/' . $slug;
        }

        if ($slug !== '/' && str_ends_with($slug, '/')) {
            $slug = rtrim($slug, '/');
        }

        return $slug;
    }

    private function getRouteMethod(\Illuminate\Routing\Route $route): string
    {
        $methods = $route->methods();
        $methods = array_diff($methods, ['HEAD']);
        
        return strtoupper(reset($methods) ?: 'GET');
    }

    /**
     * @return array<string, array{pattern: string, params: array<string>}>
     */
    public function getPageRoutes(): array
    {
        return $this->pageRoutes;
    }

    private function ensureDefaultPagesRegistered(): void
    {
        if ($this->pagesInitialized) {
            return;
        }

        $pagesPath = ink_resource_path('pages');

        if (! File::exists($pagesPath)) {
            $this->pagesInitialized = true;
            return;
        }

        foreach (File::allFiles($pagesPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $slug = $this->generateSlugFromPath($file->getPathname(), $pagesPath);

            if (! isset($this->pageRoutes[$slug])) {
                $this->pageRoutes[$slug] = [
                    'pattern' => $slug,
                    'params' => $this->extractParamsFromSlug($slug),
                    'type' => 'lara-ink',
                ];
            }
        }

        $this->pagesInitialized = true;
    }

    private function generateSlugFromPath(string $filePath, string $basePath): string
    {
        $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $filePath);
        $relativePath = str_replace('.php', '', $relativePath);

        return $this->normalizeSlug($relativePath);
    }

    /**
     * @return array<string>
     */
    private function extractParamsFromSlug(string $slug): array
    {
        if (preg_match_all('/\[([^\]]+)\]/', $slug, $matches)) {
            return $matches[1];
        }

        return [];
    }
}
