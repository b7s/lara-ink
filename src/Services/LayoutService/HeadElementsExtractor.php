<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\LayoutService;

final class HeadElementsExtractor
{
    /**
     * Extract head elements (title, meta, style) from page content
     * 
     * @param string $content
     * @return array{cleanContent: string, title: ?string, meta: string, styles: string, scopedStyles: array}
     */
    public function extractHeadElements(string $content): array
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
    public function injectHeadElements(string $layoutHtml, array $headElements): string
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

    /**
     * @param array<int, array{attributes: string, css: string}> $scopedStyles
     */
    public function renderScopedStyles(array $scopedStyles, string $pageId): string
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

    public function scopeCssToPageId(string $css, string $pageId): string
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
}
