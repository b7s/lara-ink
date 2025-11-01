<?php

declare(strict_types=1);

use B7s\LaraInk\DTOs\RouteInfo;
use Illuminate\Support\Facades\App;

if (! function_exists('ink_config')) {
    function ink_config(string $key, mixed $default = null): mixed
    {
        if($key === 'api_base_url') {
            return config("lara-ink.{$key}", config('app.url', $default));
        }

        return config("lara-ink.{$key}", $default);
    }
}

if (! function_exists('ink_path')) {
    function ink_path(string $path = ''): string
    {
        $basePath = App::basePath(ink_config('output_dir', 'public'));

        return rtrim($basePath . '/' . ltrim($path, '/'), '/');
    }
}

if (! function_exists('ink_make')) {
    function ink_make(): \B7s\LaraInk\Support\LaraInk
    {
        return new \B7s\LaraInk\Support\LaraInk();
    }
}

if (! function_exists('ink_route')) {
    /**
     * @param array<string, mixed> $params
     */
    function ink_route(string $nameOrSlug, array $params = [], ?string $method = null): RouteInfo
    {
        $routeService = app(\B7s\LaraInk\Services\RouteService::class);
        return $routeService->resolve($nameOrSlug, $params, $method);
    }
}

if (! function_exists('ink_get_css')) {
    function ink_get_css(): string
    {
        $viteService = app(\B7s\LaraInk\Services\ViteService::class);
        return $viteService->getAssetUrl('app.css');
    }
}

if (! function_exists('ink_get_js')) {
    function ink_get_js(): string
    {
        $viteService = app(\B7s\LaraInk\Services\ViteService::class);
        return $viteService->getAssetUrl('app.js');
    }
}
