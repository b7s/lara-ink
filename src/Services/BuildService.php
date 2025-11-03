<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

use B7s\LaraInk\DTOs\ParsedPage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

final class BuildService
{
    public function __construct(
        private readonly DslParserService $parserService,
        private readonly CompilerService $compilerService,
        private readonly LayoutService $layoutService,
        private readonly RouteService $routeService,
        private readonly TranslationService $translationService,
        private readonly CacheService $cacheService,
        private readonly SpaGeneratorService $spaGenerator,
        private readonly ViteService $viteService,
        private readonly AssetManagerService $assetManager,
        private readonly SeoService $seoService,
        private readonly PageRouteRegistrationService $pageRouteRegistration,
    ) {}

    /**
     * @return array{success: bool, message: string, pages: int}
     */
    public function build(): array
    {
        try {
            $pages = $this->discoverPages();
            
            if (empty($pages)) {
                return [
                    'success' => false,
                    'message' => 'No pages found in resources/lara-ink/pages/',
                    'pages' => 0,
                ];
            }

            $compiledPages = [];

            foreach ($pages as $pagePath) {
                $compiledPages[] = $this->compilePage($pagePath);
            }

            $this->cleanOutputDirectories();

            $this->generateAssets();

            $scriptPaths = $this->assetManager->prepareScripts();
            $stylePaths = $this->assetManager->prepareStyles();

            $this->generateSpaIndex($scriptPaths, $stylePaths);
            $this->generateTranslations();

            foreach ($compiledPages as $compiledPage) {
                $this->saveCompiledPage($compiledPage['slug'], $compiledPage['html']);
            }

            // Register all page routes with middleware in Laravel
            $this->pageRouteRegistration->registerRoutesInLaravel();

            return [
                'success' => true,
                'message' => 'Build completed successfully',
                'pages' => count($compiledPages),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'pages' => 0,
            ];
        }
    }

    /**
     * @return array<string>
     */
    private function discoverPages(): array
    {
        $pagesPath = ink_resource_path('pages');
        
        if (!File::exists($pagesPath)) {
            return [];
        }

        $files = File::allFiles($pagesPath);
        $pages = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $pages[] = $file->getPathname();
            }
        }

        return $pages;
    }

    private function compilePage(string $pagePath): array
    {
        $parsedPage = $this->parserService->parse($pagePath);

        $this->routeService->registerPageRoute(
            $parsedPage->slug,
            $parsedPage->slug,
            array_keys($parsedPage->params)
        );

        // Register page with middleware for Laravel route registration
        $this->pageRouteRegistration->registerPage($parsedPage);

        if ($parsedPage->config->cache !== null) {
            $this->cacheService->registerPageCache(
                $parsedPage->slug,
                $parsedPage->config->cache
            );
        }

        $this->translationService->collectKeys($parsedPage->translations);

        // Compile HTML with Blade directives converted to Alpine.js
        $html = $this->compilerService->compileHtml($parsedPage);
        $js = $this->compilerService->compileJs($parsedPage);
        $css = $this->compilerService->compileCss($parsedPage, $this->parserService->getVariables());

        if ($parsedPage->config->layout !== null) {
            // Page has layout: generate complete HTML document
            $layoutContent = $this->layoutService->resolve($parsedPage->config->getLayout());
            $this->translationService->collectKeysFromContent($layoutContent);
            $layoutData = $this->buildLayoutData($parsedPage, $js, $css);

            // The compiled HTML is already transformed; avoid Blade::render to prevent PHP execution
            $finalHtml = $this->layoutService->applyLayout($layoutContent, $html, $layoutData);
        } else {
            // No layout: generate SPA fragment
            $finalHtml = $this->buildFinalHtml($html, $js, $css, $parsedPage);
        }

        return [
            'slug' => $parsedPage->slug,
            'html' => $finalHtml,
        ];
    }

    private function buildFinalHtml(string $html, string $js, string $css, $parsedPage): string
    {
        $seoConfig = $parsedPage->config->getSeoConfig();
        $title = $this->seoService->getTitle($seoConfig, $parsedPage->config->title);
        $requiresAuth = $parsedPage->config->requiresAuth() ? 'true' : 'false';
        $middleware = $parsedPage->config->middleware 
            ? json_encode($parsedPage->config->middleware, JSON_UNESCAPED_SLASHES) 
            : '[]';
        
        // Generate Alpine variables initialization
        $alpineVarsInit = $this->compilerService->generateAlpineVariablesInit($parsedPage);
        $alpineVarsCode = $alpineVarsInit ? ",\n{$alpineVarsInit}" : '';

        // Generate SEO meta tags
        $seoMetaTags = $this->seoService->generateMetaTags($seoConfig, $title);
        $seoStructuredData = $this->seoService->generateStructuredData($seoConfig);

        $headContent = '';
        if (!empty($title)) {
            $headContent .= "<title>{$title}</title>\n";
        }
        if (!empty($seoMetaTags)) {
            $headContent .= "{$seoMetaTags}\n";
        }
        if (!empty($seoStructuredData)) {
            $headContent .= "{$seoStructuredData}\n";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    {$headContent}
</head>
<body>
<div x-data="pageData()" x-init="init()">
    <style>{$css}</style>
    {$html}
    <script>
        function pageData() {
            return {
                requiresAuth: {$requiresAuth},
                middleware: {$middleware}{$alpineVarsCode},
                
                async init() {
                    if (this.requiresAuth) {
                        const isAuth = await window.lara_ink.is_authenticated();
                        if (!isAuth) {
                            window.location.href = window.lara_ink.api_base_url + '/login';
                            return;
                        }
                    }
                    
                    {$js}
                }
            };
        }
    </script>
</div>
</body>
</html>
HTML;
    }

    private function saveCompiledPage(string $slug, string $html): void
    {
        $pagesDir = ink_project_path(ink_config('output.pages_dir', 'public/pages'));
        $pagesRoot = rtrim($pagesDir, '/');

        $relativePath = trim($slug, '/');
        if ($relativePath === '') {
            $relativePath = 'index';
        }

        $segments = explode('/', $relativePath);
        $fileName = array_pop($segments) . '.html';

        $directorySuffix = empty($segments) ? '' : DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments);
        $directory = rtrim($pagesRoot . $directorySuffix, DIRECTORY_SEPARATOR);
        $fullPath = $directory . DIRECTORY_SEPARATOR . $fileName;

        // Minify HTML before saving
        $minifiedHtml = $this->compilerService->minifyHtml($html);

        File::ensureDirectoryExists($directory);
        File::put($fullPath, $minifiedHtml);
    }

    private function buildLayoutData(ParsedPage $parsedPage, string $js = '', string $css = ''): array
    {
        $configValues = get_object_vars($parsedPage->config);

        $layoutData = [];

        foreach ($configValues as $key => $value) {
            if ($value !== null) {
                $layoutData[$key] = $value;
            }
        }

        $layoutData['config'] = $parsedPage->config;
        $layoutData['page'] = $parsedPage;
        $layoutData['title'] = $parsedPage->config->title ?? config('app.name', 'LaraInk');
        $layoutData['pageJs'] = $this->buildPageJs($js, $parsedPage);
        $layoutData['pageCss'] = $css;
        $layoutData['alpineData'] = $this->compilerService->generateAlpineVariablesInit($parsedPage);

        if (!array_key_exists('head', $layoutData)) {
            $layoutData['head'] = null;
        }

        return $layoutData;
    }
    
    private function buildPageJs(string $js, ParsedPage $parsedPage): string
    {
        $requiresAuth = $parsedPage->config->requiresAuth() ? 'true' : 'false';
        $middleware = $parsedPage->config->middleware 
            ? json_encode($parsedPage->config->middleware, JSON_UNESCAPED_SLASHES) 
            : '[]';
        $alpineVarsInit = $this->compilerService->generateAlpineVariablesInit($parsedPage);
        $alpineVarsCode = $alpineVarsInit ? ",\n{$alpineVarsInit}" : '';
        
        return <<<JS
function pageData() {
    return {
        requiresAuth: {$requiresAuth},
        middleware: {$middleware}{$alpineVarsCode},
        
        async init() {
            if (this.requiresAuth) {
                const isAuth = await window.lara_ink.is_authenticated();
                if (!isAuth) {
                    window.location.href = window.lara_ink.api_base_url + '/login';
                    return;
                }
            }
            
            {$js}
        }
    };
}
JS;
    }

    private function generateAssets(): void
    {
        $assetsPath = ink_resource_path('assets');

        if (!File::exists($assetsPath)) {
            File::makeDirectory($assetsPath, 0755, true);
        }

        $appCssPath = $assetsPath . DIRECTORY_SEPARATOR . 'app.css';
        if (!File::exists($appCssPath)) {
            $defaultCss = <<<'CSS'
/* LaraInk App CSS */

/* 
 * To use Tailwind CSS 4:
 * 
 * 1. Install dependencies:
 *    npm install -D tailwindcss @tailwindcss/vite
 * 
 * 2. Add to your vite.config.js:
 *    import tailwindcss from '@tailwindcss/vite';
 *    plugins: [tailwindcss()]
 * 
 * 3. Add at the top of this file:
 *    @import "tailwindcss";
 *    @source "../../lara-ink/pages/**/*.php";
 *    @source "../../lara-ink/layouts/**/*.php";
 */
CSS;
            File::put($appCssPath, $defaultCss);
        }

        $appJsPath = $assetsPath . DIRECTORY_SEPARATOR . 'app.js';
        if (!File::exists($appJsPath)) {
            File::put($appJsPath, "import './app.css';\n// LaraInk App JS");
        }

        $this->viteService->build();
        
        $buildDir = ink_project_path(ink_config('output.build_dir', 'public/build'));

        $this->viteService->generateManifest($buildDir);
        
        // Copy SPA router script to build directory
        $this->copySpaScript($buildDir);
    }
    
    private function copySpaScript(string $buildDir): void
    {
        $spaScriptSource = __DIR__ . '/../../stubs/lara-ink-spa.js';
        $spaScriptDest = rtrim($buildDir, DIRECTORY_SEPARATOR) . '/lara-ink-spa.js';
        
        if (File::exists($spaScriptSource)) {
            File::ensureDirectoryExists($buildDir);
            File::copy($spaScriptSource, $spaScriptDest);
        }
    }

    /**
     * @param array<int, string> $scriptPaths
     * @param array<int, string> $stylePaths
     */
    private function generateSpaIndex(array $scriptPaths, array $stylePaths): void
    {
        $outputPath = ink_project_path(ink_config('output.dir', 'public') . '/index.html');
        $this->spaGenerator->generateIndexHtml($outputPath, $scriptPaths, $stylePaths);
    }

    private function generateTranslations(): void
    {
        $outputPath = ink_project_path(ink_config('output.build_dir', 'public/build') . '/lara-ink-lang.js');
        $this->translationService->generateJsFile($outputPath);
    }

    private function cleanOutputDirectories(): void
    {
        $pagesDir = ink_project_path(ink_config('output.pages_dir', 'public/pages'));
        $buildDir = ink_project_path(ink_config('output.build_dir', 'public/build'));

        if (File::exists($pagesDir)) {
            File::cleanDirectory($pagesDir);
        }

        if (File::exists($buildDir)) {
            File::cleanDirectory($buildDir);
        }
    }

    /**
     * Build only specific file(s) based on changed path
     * 
     * @return array{success: bool, message: string, pages: int, type: string}
     */
    public function buildSelective(string $changedPath): array
    {
        try {
            // Normalize path
            $changedPath = str_replace('\\', '/', $changedPath);
            $relativePath = str_replace(base_path() . '/', '', $changedPath);
            $type = $this->detectChangeType($relativePath);
            $pagesToBuild = [];

            match ($type) {
                'page' => $pagesToBuild = [$changedPath],
                'layout' => $pagesToBuild = $this->getPagesUsingLayout($changedPath),
                'component' => $pagesToBuild = $this->getPagesUsingComponent($changedPath),
                default => $pagesToBuild = $this->discoverPages(), // Fallback to full build
            };

            if (empty($pagesToBuild)) {
                return [
                    'success' => false,
                    'message' => "No pages to rebuild for {$type}: " . basename($changedPath),
                    'pages' => 0,
                    'type' => $type,
                ];
            }

            $compiledPages = [];
            foreach ($pagesToBuild as $pagePath) {
                if (!File::exists($pagePath)) {
                    continue;
                }
                
                try {
                    $compiledPages[] = $this->compilePage($pagePath);
                } catch (\Exception $e) {
                    // Log error but continue with other pages
                    Log::error("Failed to compile page: {$pagePath}", ['error' => $e->getMessage()]);
                }
            }

            if (empty($compiledPages)) {
                return [
                    'success' => false,
                    'message' => "Failed to compile any pages. Check if file exists: {$changedPath}",
                    'pages' => 0,
                    'type' => $type,
                ];
            }

            // Only regenerate assets if needed
            if ($type !== 'page') {
                $this->generateAssets();
                $scriptPaths = $this->assetManager->prepareScripts();
                $stylePaths = $this->assetManager->prepareStyles();
                $this->generateSpaIndex($scriptPaths, $stylePaths);
                $this->generateTranslations();
            }

            foreach ($compiledPages as $compiledPage) {
                $this->saveCompiledPage($compiledPage['slug'], $compiledPage['html']);
            }

            return [
                'success' => true,
                'message' => "Rebuilt {$type}: " . basename($changedPath),
                'pages' => count($compiledPages),
                'type' => $type,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'pages' => 0,
                'type' => 'error',
            ];
        }
    }

    private function detectChangeType(string $relativePath): string
    {
        if (str_contains($relativePath, 'resources/lara-ink/pages/')) {
            return 'page';
        }
        if (str_contains($relativePath, 'resources/lara-ink/layouts/')) {
            return 'layout';
        }
        if (str_contains($relativePath, 'resources/lara-ink/components/')) {
            return 'component';
        }
        return 'other';
    }

    /**
     * @return array<string>
     */
    private function getPagesUsingLayout(string $layoutPath): array
    {
        $layoutName = basename($layoutPath, '.php');
        $pages = $this->discoverPages();
        $affectedPages = [];

        foreach ($pages as $pagePath) {
            $content = File::get($pagePath);
            // Check if page uses this layout
            if (preg_match("/->layout\(['\"]" . preg_quote($layoutName, '/') . "['\"]\)/", $content)) {
                $affectedPages[] = $pagePath;
            }
        }

        return $affectedPages;
    }

    /**
     * @return array<string>
     */
    private function getPagesUsingComponent(string $componentPath): array
    {
        // Extract component name from path
        $componentName = str_replace(
            [ink_resource_path('components') . '/', '.php'],
            ['', ''],
            $componentPath
        );
        $componentName = str_replace('/', '.', $componentName);

        $pages = $this->discoverPages();
        $affectedPages = [];

        foreach ($pages as $pagePath) {
            $content = File::get($pagePath);
            // Check if page uses this component
            $pattern = "/<x-" . preg_quote($componentName, '/') . "[\s\/>]/";
            if (preg_match($pattern, $content)) {
                $affectedPages[] = $pagePath;
            }
        }

        return $affectedPages;
    }
}
