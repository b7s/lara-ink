<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;

final class LayoutService
{
    private const TRANSLATION_PLACEHOLDER_PREFIX = '__LARA_INK_TRANS__';

    public function __construct(
        private readonly SeoService $seoService,
    ) {}

    public function resolve(string $layoutName): string
    {
        $layoutPath = $this->findLayout($layoutName);

        if ($layoutPath === null) {
            throw new \RuntimeException("Layout not found: {$layoutName}");
        }

        $content = File::get($layoutPath);

        return $content;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function applyLayout(string $layoutContent, string $pageContent, array $data = []): string
    {
        // Extract head elements from page content
        $headElements = $this->extractHeadElements($pageContent);
        
        $pageId = $data['page']->id ?? 'page-' . bin2hex(random_bytes(4));
        $scopedStyles = $headElements['scopedStyles'] ?? [];
        $scopedStyleTags = $this->renderScopedStyles($scopedStyles, $pageId);

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
        $layoutContent = $this->extractTranslationPlaceholders($layoutContent, $layoutPlaceholders);

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
        
        $userLayoutHtml = $this->restoreTranslationPlaceholders($userLayoutHtml, $layoutPlaceholders);

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
    private function wrapInDefaultLayout(string $userLayoutHtml, array $data, array $headElements): string
    {
        $defaultLayoutPath = __DIR__ . '/../../stubs/default-layout.blade.php';
        
        if (!File::exists($defaultLayoutPath)) {
            throw new \RuntimeException('Default layout not found: ' . $defaultLayoutPath);
        }
        
        $defaultLayout = File::get($defaultLayoutPath);
        $defaultPlaceholders = [];
        $defaultLayout = $this->extractTranslationPlaceholders($defaultLayout, $defaultPlaceholders);

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

        return $this->restoreTranslationPlaceholders($rendered, $defaultPlaceholders);
    }

    /**
     * @param array<int, string> $placeholders
     */
    private function extractTranslationPlaceholders(string $content, array &$placeholders): string
    {
        $patterns = [
            ['pattern' => '/\{\{\s*__\(\s*[\'\"]([^\'\"]+)[\'\"]\s*(?:,[^}]*)?\)\s*\}\}/', 'type' => 'text'],
            ['pattern' => '/\{\{\s*trans\(\s*[\'\"]([^\'\"]+)[\'\"]\s*(?:,[^}]*)?\)\s*\}\}/', 'type' => 'text'],
            ['pattern' => '/\{\{\s*trans_choice\(\s*[\'\"]([^\'\"]+)[\'\"]\s*,[^}]*\)\s*\}\}/', 'type' => 'text'],
            ['pattern' => '/\{\{\s*@lang\(\s*[\'\"]([^\'\"]+)[\'\"]\s*(?:,[^}]*)?\)\s*\}\}/', 'type' => 'text'],
            ['pattern' => '/\{!!\s*__\(\s*[\'\"]([^\'\"]+)[\'\"]\s*(?:,[^}]*)?\)\s*!!\}/', 'type' => 'html'],
            ['pattern' => '/\{!!\s*trans\(\s*[\'\"]([^\'\"]+)[\'\"]\s*(?:,[^}]*)?\)\s*!!\}/', 'type' => 'html'],
            ['pattern' => '/\{!!\s*trans_choice\(\s*[\'\"]([^\'\"]+)[\'\"]\s*,[^}]*\)\s*!!\}/', 'type' => 'html'],
            ['pattern' => '/\{!!\s*@lang\(\s*[\'\"]([^\'\"]+)[\'\"]\s*(?:,[^}]*)?\)\s*!!\}/', 'type' => 'html'],
            ['pattern' => '/@lang\(\s*[\'\"]([^\'\"]+)[\'\"]\s*(?:,[^)]*)?\)/', 'type' => 'text'],
        ];

        foreach ($patterns as $config) {
            $pattern = $config['pattern'];
            $type = $config['type'];

            $content = preg_replace_callback(
                $pattern,
                function (array $matches) use (&$placeholders, $type): string {
                    $key = $matches[1];
                    $token = $this->generateTranslationToken($key, $type, $placeholders);

                    return $token;
                },
                $content
            );
        }

        return $content;
    }

    /**
     * @param array<string, array{key: string, type: string}> $placeholders
     */
    private function restoreTranslationPlaceholders(string $content, array $placeholders): string
    {
        foreach ($placeholders as $token => $data) {
            $key = $data['key'];
            $type = $data['type'];
            $escapedKey = str_replace("'", "\\'", $key);
            $fallback = htmlspecialchars($key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $replacement = $type === 'html'
                ? sprintf('<span x-html="lara_ink.trans(\'%s\')">%s</span>', $escapedKey, $fallback)
                : sprintf('<span x-text="lara_ink.trans(\'%s\')">%s</span>', $escapedKey, $fallback);

            $content = str_replace($token, $replacement, $content);
        }

        return $content;
    }

    /**
     * @param array<string, array{key: string, type: string}> $placeholders
     */
    private function generateTranslationToken(string $key, string $type, array &$placeholders): string
    {
        $token = self::TRANSLATION_PLACEHOLDER_PREFIX . str_replace('.', '_', uniqid('', true)) . '__';
        $placeholders[$token] = [
            'key' => $key,
            'type' => $type,
        ];

        return $token;
    }

    private function renderScopedStyles(array $scopedStyles, string $pageId): string
    {
        if ($scopedStyles === []) {
            return '';
        }

        $rendered = array_map(function (array $style) use ($pageId) {
            $attributes = isset($style['attributes']) && trim($style['attributes']) !== ''
                ? ' ' . trim($style['attributes'])
                : '';

            $scopedCss = $this->scopeCssToPageId($style['css'] ?? '', $pageId);

            return '<style data-page-id="' . $pageId . '"' . $attributes . '>' . $scopedCss . '</style>';
        }, $scopedStyles);

        return implode(PHP_EOL, $rendered);
    }

    private function scopeCssToPageId(string $css, string $pageId): string
    {
        $pattern = '/([^{}]+){([^}]*)}/m';

        return preg_replace_callback($pattern, function (array $matches) use ($pageId) {
            $selectors = array_filter(array_map('trim', explode(',', $matches[1])));

            $scopedSelectors = array_map(
                fn (string $selector) => '#' . $pageId . ' ' . $selector,
                $selectors
            );

            return implode(', ', $scopedSelectors) . ' {' . $matches[2] . '}';
        }, $css) ?? $css;
    }
    
    /**
     * Extract head elements (title, meta, style) from page content
     * 
     * @param string $content
     * @return array{cleanContent: string, title: ?string, meta: string, styles: string, scopedStyles: array}
     */
    private function extractHeadElements(string $content): array
    {
        $title = null;
        $meta = [];
        $globalStyles = [];
        $scopedStyles = [];

        // Extract <title>
        if (preg_match('/<title>(.*?)<\/title>/is', $content, $matches)) {
            $title = trim($matches[1]);
            $content = str_replace($matches[0], '', $content);
        }

        // Extract <meta> tags
        if (preg_match_all('/<meta[^>]*>/is', $content, $matches)) {
            $meta = $matches[0];

            foreach ($matches[0] as $tag) {
                $content = str_replace($tag, '', $content);
            }
        }

        // Extract <style> tags (global and scoped)
        if (preg_match_all('/<style([^>]*)>(.*?)<\/style>/is', $content, $styleMatches, PREG_SET_ORDER)) {
            foreach ($styleMatches as $styleMatch) {
                $attributes = $styleMatch[1] ?? '';
                $css = $styleMatch[2] ?? '';

                if (stripos($attributes, 'scoped') !== false) {
                    $cleanAttributes = preg_replace('/\s*scoped\b/i', '', $attributes) ?? $attributes;
                    $scopedStyles[] = [
                        'attributes' => trim($cleanAttributes),
                        'css' => trim($css),
                    ];
                } else {
                    $globalStyles[] = $styleMatch[0];
                }

                $content = str_replace($styleMatch[0], '', $content);
            }
        }

        return [
            'cleanContent' => trim($content),
            'title' => $title,
            'meta' => implode("\n", $meta),
            'styles' => implode("\n", $globalStyles),
            'scopedStyles' => $scopedStyles,
        ];
    }
    
    /**
     * Inject head elements into the layout
     * 
     * @param string $layoutHtml
     * @param array{title: ?string, meta: string, styles: string} $headElements
     * @return string
     */
    private function injectHeadElements(string $layoutHtml, array $headElements): string
    {
        // Try to inject before </head>
        if (str_contains($layoutHtml, '</head>')) {
            $injection = '';
            
            if (!empty($headElements['title'])) {
                // Replace existing title or inject new one
                if (preg_match('/<title>.*?<\\/title>/is', $layoutHtml)) {
                    $layoutHtml = preg_replace(
                        '/<title>.*?<\\/title>/is',
                        '<title>' . $headElements['title'] . '</title>',
                        $layoutHtml
                    );
                } else {
                    $injection .= '<title>' . $headElements['title'] . '</title>' . "\n";
                }
            }
            
            if (!empty($headElements['meta'])) {
                $injection .= $headElements['meta'] . "\n";
            }
            
            if (!empty($headElements['styles'])) {
                $injection .= $headElements['styles'] . "\n";
            }
            
            if ($injection) {
                $layoutHtml = str_replace('</head>', $injection . '</head>', $layoutHtml);
            }
        }
        
        return $layoutHtml;
    }

    private function findLayout(string $layoutName): ?string
    {
        $basePath = rtrim(ink_resource_path('layouts'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $layoutName = str_replace('.', '/', $layoutName);
        
        $possiblePaths = [
            $basePath . $layoutName . '.php',
            $basePath . $layoutName . '.blade.php',
        ];

        foreach ($possiblePaths as $path) {
            if (File::exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return array<string>
     */
    public function getAllLayouts(): array
    {
        $basePath = rtrim(ink_resource_path('layouts'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        
        if (!File::exists($basePath)) {
            return [];
        }

        $files = File::allFiles($basePath);
        $layouts = [];

        foreach ($files as $file) {
            $relativePath = str_replace($basePath, '', $file->getPathname());
            $relativePath = str_replace('.php', '', $relativePath);
            $relativePath = str_replace('.blade.php', '', $relativePath);
            $layouts[] = str_replace('/', '.', $relativePath);
        }

        return $layouts;
    }
}
