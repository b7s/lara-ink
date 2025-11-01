<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

use B7s\LaraInk\DTOs\ParsedPage;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

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
    ) {}

    /**
     * @return array{success: bool, message: string, pages: int}
     */
    public function build(): array
    {
        try {
            $this->cleanOutputDirectories();
            
            $pages = $this->discoverPages();
            
            if (empty($pages)) {
                return [
                    'success' => false,
                    'message' => 'No pages found in resources/lara-ink/pages/',
                    'pages' => 0,
                ];
            }

            foreach ($pages as $pagePath) {
                $this->buildPage($pagePath);
            }

            $this->generateAssets();

            $scriptPaths = $this->assetManager->prepareScripts();
            $stylePaths = $this->assetManager->prepareStyles();

            $this->generateSpaIndex($scriptPaths, $stylePaths);
            $this->generateTranslations();

            return [
                'success' => true,
                'message' => 'Build completed successfully',
                'pages' => count($pages),
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
        $pagesPath = App::basePath('resources/lara-ink/pages/');
        
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

    private function buildPage(string $pagePath): void
    {
        $parsedPage = $this->parserService->parse($pagePath);

        $this->routeService->registerPageRoute(
            $parsedPage->slug,
            $parsedPage->slug,
            array_keys($parsedPage->params)
        );

        if ($parsedPage->config->cache !== null) {
            $this->cacheService->registerPageCache(
                $parsedPage->slug,
                $parsedPage->config->cache
            );
        }

        $this->translationService->collectKeys($parsedPage->translations);

        $html = $this->compilerService->compileHtml($parsedPage);
        $js = $this->compilerService->compileJs($parsedPage);
        $css = $this->compilerService->compileCss($parsedPage, $this->parserService->getVariables());

        if ($parsedPage->config->layout !== null) {
            $layoutContent = $this->layoutService->resolve($parsedPage->config->getLayout());
            $layoutData = $this->buildLayoutData($parsedPage);
            $html = $this->layoutService->applyLayout($layoutContent, $html, $layoutData);
        }

        $finalHtml = $this->buildFinalHtml($html, $js, $css, $parsedPage);

        $this->saveCompiledPage($parsedPage->slug, $finalHtml);
    }

    private function buildFinalHtml(string $html, string $js, string $css, $parsedPage): string
    {
        $title = $parsedPage->config->title ?? 'Page';
        $requiresAuth = $parsedPage->config->requiresAuth() ? 'true' : 'false';
        $middleware = $parsedPage->config->middleware ?? '';

        return <<<HTML
<div x-data="pageData()" x-init="init()">
    <style>{$css}</style>
    {$html}
    <script>
        function pageData() {
            return {
                requiresAuth: {$requiresAuth},
                middleware: '{$middleware}',
                
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
HTML;
    }

    private function saveCompiledPage(string $slug, string $html): void
    {
        $outputDir = ink_config('output.pages_dir', 'public/pages');
        $fullPath = App::basePath($outputDir . $slug . '.html');

        File::ensureDirectoryExists(dirname($fullPath));
        File::put($fullPath, $html);
    }

    private function buildLayoutData(\B7s\LaraInk\DTOs\ParsedPage $parsedPage): array
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

        if (!array_key_exists('head', $layoutData)) {
            $layoutData['head'] = null;
        }

        return $layoutData;
    }

    private function generateAssets(): void
    {
        $assetsPath = App::basePath('resources/lara-ink/assets/');
        
        if (!File::exists($assetsPath)) {
            File::makeDirectory($assetsPath, 0755, true);
        }

        $appJsPath = $assetsPath . 'app.js';
        if (!File::exists($appJsPath)) {
            File::put($appJsPath, '// LaraInk App JS');
        }

        $appCssPath = $assetsPath . 'app.css';
        if (!File::exists($appCssPath)) {
            File::put($appCssPath, '/* LaraInk App CSS */');
        }

        $this->viteService->build();
        
        $buildDir = App::basePath(ink_config('output.build_dir', 'public/build'));
        $this->viteService->generateManifest($buildDir);
    }

    /**
     * @param array<int, string> $scriptPaths
     * @param array<int, string> $stylePaths
     */
    private function generateSpaIndex(array $scriptPaths, array $stylePaths): void
    {
        $outputPath = App::basePath(ink_config('output.dir', 'public') . '/index.html');
        $this->spaGenerator->generateIndexHtml($outputPath, $scriptPaths, $stylePaths);
    }

    private function generateTranslations(): void
    {
        $outputPath = App::basePath(ink_config('output.build_dir', 'public/build') . '/lara-ink-lang.js');
        $this->translationService->generateJsFile($outputPath);
    }

    private function cleanOutputDirectories(): void
    {
        $pagesDir = App::basePath(ink_config('output.pages_dir', 'public/pages'));
        $buildDir = App::basePath(ink_config('output.build_dir', 'public/build'));

        if (File::exists($pagesDir)) {
            File::cleanDirectory($pagesDir);
        }

        if (File::exists($buildDir)) {
            File::cleanDirectory($buildDir);
        }
    }
}
