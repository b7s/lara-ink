<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\LayoutService;

use B7s\LaraInk\Services\SeoService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;

final class LayoutRenderer
{
    public function __construct(
        private readonly SeoService $seoService,
        private readonly HeadElementsExtractor $headElementsExtractor,
        private readonly TranslationPlaceholderHandler $translationPlaceholderHandler,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public function applyLayout(string $layoutContent, string $pageContent, array $data = []): string
    {
        // Extract head elements from page content
        $headElements = $this->headElementsExtractor->extractHeadElements($pageContent);
        
        $pageId = $data['page']->id ?? 'page-' . bin2hex(random_bytes(4));
        $scopedStyles = $headElements['scopedStyles'] ?? [];
        $scopedStyleTags = $this->headElementsExtractor->renderScopedStyles($scopedStyles, $pageId);

        $contentWithStyles = $headElements['cleanContent'];
        if ($scopedStyleTags !== '') {
            $contentWithStyles .= PHP_EOL . $scopedStyleTags;
        }

        $wrappedPageContent = '<div id="' . $pageId . '" x-data="pageData()" x-init="init()">' . PHP_EOL .
            $contentWithStyles . PHP_EOL .
            '</div>';

        // Check if layout uses @yield('page')
        $usesYield = str_contains($layoutContent, "@yield('page')");
        
        $layoutPlaceholders = [];
        $layoutContent = $this->translationPlaceholderHandler->extractTranslationPlaceholders($layoutContent, $layoutPlaceholders);

        if ($usesYield) {
            // Replace the first @yield('page') with a placeholder variable
            $layoutWithPlaceholder = preg_replace(
                "/@yield\(\s*['\"]page['\"]\s*(?:,\s*[^)]*)?\)/",
                "{{ \$__laraInkPageContent }}",
                $layoutContent,
                1
            );

            if ($layoutWithPlaceholder === null) {
                throw new \RuntimeException('Failed to process @yield(\'page\') in layout.');
            }

            $viewData = array_merge($data, [
                '__laraInkPageContent' => new HtmlString($wrappedPageContent),
            ]);

            $userLayoutHtml = Blade::render($layoutWithPlaceholder, $viewData);
        } else {
            // Fallback to $slot for backward compatibility
            $userLayoutHtml = Blade::render($layoutContent, array_merge($data, [
                'slot' => new HtmlString($wrappedPageContent),
            ]));
        }
        
        $userLayoutHtml = $this->translationPlaceholderHandler->restoreTranslationPlaceholders($userLayoutHtml, $layoutPlaceholders);

        // Wrap user layout in default wrapper
        return $this->wrapInDefaultLayout($userLayoutHtml, $data, $headElements);
    }
    
    /**
     * Wrap user layout content in the default LaraInk layout wrapper
     * 
     * @param string $userLayoutHtml
     * @param array<string, mixed> $data
     * @param array{cleanContent: string, title: ?string, meta: string, styles: string} $headElements
     * @return string
     */
    public function wrapInDefaultLayout(string $userLayoutHtml, array $data, array $headElements): string
    {
        $defaultLayoutPath = __DIR__ . '/../../../stubs/default-layout.blade.php';
        
        if (!File::exists($defaultLayoutPath)) {
            throw new \RuntimeException('Default layout not found: ' . $defaultLayoutPath);
        }
        
        $defaultLayout = File::get($defaultLayoutPath);
        $defaultPlaceholders = [];
        $defaultLayout = $this->translationPlaceholderHandler->extractTranslationPlaceholders($defaultLayout, $defaultPlaceholders);

        // Generate SEO meta tags
        $seoConfig = $data['config']->getSeoConfig() ?? null;
        $seoMetaTags = $this->seoService->generateMetaTags($seoConfig, $data['title'] ?? null);
        $seoTitle = $this->seoService->getTitle($seoConfig, $data['title'] ?? null);
        $seoStructuredData = $this->seoService->generateStructuredData($seoConfig);

        $viewData = array_merge($data, [
            'userLayout' => new HtmlString($userLayoutHtml),
            'slot' => new HtmlString(''), // Not used when userLayout is provided
            'pageTitle' => $seoTitle,
            'pageMeta' => $headElements['meta'] ?? '',
            'pageStyles' => $headElements['styles'] ?? '',
            'pageId' => $data['page']->id ?? null,
            'seoMetaTags' => $seoMetaTags,
            'seoStructuredData' => $seoStructuredData,
        ]);
        
        $rendered = Blade::render($defaultLayout, $viewData);

        return $this->translationPlaceholderHandler->restoreTranslationPlaceholders($rendered, $defaultPlaceholders);
    }
}
