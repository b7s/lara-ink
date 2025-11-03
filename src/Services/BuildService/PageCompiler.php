<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\BuildService;

use B7s\LaraInk\DTOs\ParsedPage;
use B7s\LaraInk\Services\CacheService;
use B7s\LaraInk\Services\CompilerService;
use B7s\LaraInk\Services\DslParserService;
use B7s\LaraInk\Services\LayoutService;
use B7s\LaraInk\Services\PageRouteRegistrationService;
use B7s\LaraInk\Services\RouteService;
use B7s\LaraInk\Services\SeoService;
use B7s\LaraInk\Services\TranslationService;

final class PageCompiler
{
    public function __construct(
        private readonly DslParserService $parserService,
        private readonly CompilerService $compilerService,
        private readonly LayoutService $layoutService,
        private readonly RouteService $routeService,
        private readonly TranslationService $translationService,
        private readonly CacheService $cacheService,
        private readonly SeoService $seoService,
        private readonly PageRouteRegistrationService $pageRouteRegistration,
    ) {}

    /**
     * @return array{slug: string, html: string}
     */
    public function compilePage(string $pagePath): array
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

    private function buildFinalHtml(string $html, string $js, string $css, ParsedPage $parsedPage): string
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

    /**
     * @return array<string, mixed>
     */
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
}
