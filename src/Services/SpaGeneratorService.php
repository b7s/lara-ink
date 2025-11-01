<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

use Illuminate\Support\Facades\File;

final class SpaGeneratorService
{
    public function __construct(
        private readonly RouteService $routeService,
        private readonly CacheService $cacheService,
    ) {}

    /**
     * @param array<int, string> $scriptPaths
     * @param array<int, string> $stylePaths
     */
    public function generateIndexHtml(string $outputPath, array $scriptPaths, array $stylePaths): void
    {
        $routes = $this->routeService->getPageRoutes();
        $cacheManifest = $this->cacheService->generateCacheManifest();

        $scriptTags = $this->buildScriptTags($scriptPaths);
        $styleTags = $this->buildStyleTags($stylePaths);

        $html = $this->buildIndexTemplate($routes, $cacheManifest, $scriptTags, $styleTags);

        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, $html);
    }

    /**
     * @param array<string, array{pattern: string, params: array<string>}> $routes
     */
    private function buildIndexTemplate(array $routes, string $cacheManifest, string $scriptTags, string $styleTags): string
    {
        $routesJson = json_encode($routes, JSON_PRETTY_PRINT);
        $apiBaseUrl = url((ink_config('api_base_url') ?? '') . ink_config('auth.route.prefix', '/api/ink'));
        $loginRoute = ink_config('auth.route.login', '/login');
        $unauthorizedRoute = ink_config('auth.route.unauthorized', '/unauthorized');

        // Load template stub
        $templatePath = __DIR__ . '/../../stubs/index-template.blade.php';
        $template = file_get_contents($templatePath);
        
        if ($template === false) {
            throw new \RuntimeException('Failed to load index template stub');
        }

        // Replace placeholders with actual values
        $template = str_replace(
            [
                '__ROUTES_JSON__',
                '__CACHE_MANIFEST__',
                '__API_BASE_URL__',
                '__LOGIN_ROUTE__',
                '__APP_TITLE__',
                '__APP_LOCALE__',
                '__SCRIPT_TAGS__',
                '__STYLE_TAGS__',
            ],
            [
                $routesJson,
                $cacheManifest,
                $apiBaseUrl,
                $loginRoute,
                config('app.name', ink_config('name', '✒️ LaraInk')),
                app()->getLocale(),
                $scriptTags,
                $styleTags,
            ],
            $template
        );

        return $template;
    }

    /**
     * @param array<int, string> $scriptPaths
     */
    private function buildScriptTags(array $scriptPaths): string
    {
        return collect($scriptPaths)
            ->map(fn (string $path) => sprintf('<script src="%s"></script>', $path))
            ->implode(PHP_EOL . '    ');
    }

    /**
     * @param array<int, string> $stylePaths
     */
    private function buildStyleTags(array $stylePaths): string
    {
        if (empty($stylePaths)) {
            return '';
        }

        return collect($stylePaths)
            ->map(fn (string $path) => sprintf('<link rel="stylesheet" href="%s">', $path))
            ->implode(PHP_EOL . '    ');
    }
}