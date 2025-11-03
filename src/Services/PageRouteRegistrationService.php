<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

use B7s\LaraInk\DTOs\ParsedPage;
use Illuminate\Support\Facades\Route;

final class PageRouteRegistrationService
{
    /**
     * @var array<string, array{slug: string, middleware: array<int, string>, auth: bool}>
     */
    private array $pageRoutes = [];

    /**
     * Register a page route with its middleware configuration
     */
    public function registerPage(ParsedPage $parsedPage): void
    {
        $this->pageRoutes[$parsedPage->slug] = [
            'slug' => $parsedPage->slug,
            'middleware' => $parsedPage->config->middleware ?? [],
            'auth' => $parsedPage->config->auth,
        ];
    }

    /**
     * Register all collected page routes in Laravel's router
     * This should be called after all pages are compiled
     */
    public function registerRoutesInLaravel(): void
    {
        foreach ($this->pageRoutes as $routeData) {
            $this->registerSingleRoute($routeData);
        }
    }

    /**
     * @param array{slug: string, middleware: array<int, string>, auth: bool} $routeData
     */
    private function registerSingleRoute(array $routeData): void
    {
        $slug = $routeData['slug'];
        $middleware = $routeData['middleware'];
        $auth = $routeData['auth'];

        // Normalize slug for route registration
        $routePath = $slug === '/index' ? '/' : $slug;
        
        // Convert [param] syntax to {param} for Laravel routes
        $routePath = preg_replace('/\[([^\]]+)\]/', '{$1}', $routePath);

        // Build middleware array
        $routeMiddleware = ['web'];
        
        if ($auth || !empty($middleware)) {
            $routeMiddleware[] = 'auth:sanctum';
        }
        
        if (!empty($middleware)) {
            $routeMiddleware = array_merge($routeMiddleware, $middleware);
        }

        // Register the route
        Route::middleware($routeMiddleware)
            ->get($routePath, function () use ($slug) {
                $htmlPath = ink_project_path(ink_config('output.pages_dir', 'public/pages')) 
                    . str_replace('/index', '/index', $slug) . '.html';
                
                if (file_exists($htmlPath)) {
                    return response()->file($htmlPath);
                }
                
                abort(404);
            })
            ->name('lara-ink.page.' . ltrim(str_replace('/', '.', $slug), '.'));
    }

    /**
     * Get all registered page routes
     * 
     * @return array<string, array{slug: string, middleware: array<int, string>, auth: bool}>
     */
    public function getRegisteredPages(): array
    {
        return $this->pageRoutes;
    }

    /**
     * Clear all registered routes
     */
    public function clear(): void
    {
        $this->pageRoutes = [];
    }
}
