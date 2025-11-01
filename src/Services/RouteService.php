<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

use B7s\LaraInk\DTOs\RouteInfo;
use Illuminate\Support\Facades\Route;

final class RouteService
{
    /**
     * @var array<string, array{pattern: string, params: array<string>}>
     */
    private array $pageRoutes = [];

    /**
     * @param array<string> $params
     */
    public function registerPageRoute(string $slug, string $pattern, array $params): void
    {
        $this->pageRoutes[$slug] = [
            'pattern' => $pattern,
            'params' => $params,
        ];
    }

    /**
     * @param array<string, mixed> $params
     */
    public function resolve(string $nameOrSlug, array $params = [], ?string $method = null): RouteInfo
    {
        if (isset($this->pageRoutes[$nameOrSlug])) {
            $url = $this->buildPageUrl($nameOrSlug, $params);
            return new RouteInfo($url, $method ?? 'GET');
        }

        if (Route::has($nameOrSlug)) {
            $url = route($nameOrSlug, $params);
            $route = Route::getRoutes()->getByName($nameOrSlug);
            $routeMethod = $method ?? ($route ? $this->getRouteMethod($route) : 'GET');
            
            return new RouteInfo($url, $routeMethod);
        }

        throw new \RuntimeException("Route or page not found: {$nameOrSlug}");
    }

    /**
     * @param array<string, mixed> $params
     */
    private function buildPageUrl(string $slug, array $params): string
    {
        $route = $this->pageRoutes[$slug];
        $url = $slug;

        foreach ($route['params'] as $param) {
            $value = $params[$param] ?? '';
            $url = preg_replace('/\[' . preg_quote($param, '/') . '\]/', (string) $value, $url);
        }

        return $url;
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
}
