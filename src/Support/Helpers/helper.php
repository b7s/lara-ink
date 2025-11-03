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

if (! function_exists('ink_project_path')) {
    function ink_project_path(string $path = ''): string
    {
        $basePath = App::basePath();

        $vendorSegment = DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;
        if (str_contains($basePath, $vendorSegment)) {
            [$beforeVendor] = explode($vendorSegment, $basePath, 2);
            if ($beforeVendor !== '') {
                $basePath = rtrim($beforeVendor, DIRECTORY_SEPARATOR);
            }
        }

        return rtrim($basePath . '/' . ltrim($path, '/'), '/');
    }
}

if (! function_exists('ink_resource_path')) {
    function ink_resource_path(string $path = ''): string
    {
        $resourceBase = 'resources/lara-ink';
        $suffix = $path !== '' ? '/' . ltrim($path, '/') : '';

        return ink_project_path($resourceBase . $suffix);
    }
}

if (! function_exists('ink_path')) {
    function ink_path(string $path = ''): string
    {
        $outputBase = ink_config('output.dir', 'public');

        $basePath = rtrim($outputBase, '/');
        $suffix = $path !== '' ? '/' . ltrim($path, '/') : '';

        return rtrim(ink_project_path($basePath . $suffix), '/');
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
     * @param $nameOrSlug string LaraInk route name or laravel route name
     * @param $params array Parameters for the route
     * @param $method string|null HTTP method for the route (get, post, put, delete, patch)
     * 
     * @return RouteInfo
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

if (! function_exists('ink_asset_url')) {
    /**
     * Generate web URL for LaraInk assets based on output.dir configuration
     */
    function ink_asset_url(string $path = ''): string
    {
        $outputDir = ink_config('output.dir', 'public/lara-ink');
        
        // Remove 'public/' prefix if present
        $webPath = $outputDir;
        if (str_starts_with($webPath, 'public/')) {
            $webPath = substr($webPath, strlen('public/'));
        }
        
        $webPath = '/' . trim($webPath, '/');
        
        if ($path !== '') {
            $webPath .= '/' . ltrim($path, '/');
        }
        
        return $webPath;
    }
}

if (! function_exists('ink_cached_script')) {
    /**
     * Get cached URL for external script
     * 
     * @param string $url External script URL
     * @return string Cached script URL
     */
    function ink_cached_script(string $url): string
    {
        $cacheService = app(\B7s\LaraInk\Services\ExternalScriptCacheService::class);
        return $cacheService->getCachedScriptUrl($url);
    }
}
