<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\CompilerService;

use B7s\LaraInk\DTOs\ParsedPage;

final class CssCompiler
{
    public function compile(ParsedPage $page, array $variables): string
    {
        if (!isset($variables['styles']) || !is_array($variables['styles'])) {
            return '';
        }

        $cssOutput = [];

        foreach ($variables['styles'] as $style) {
            if (!is_array($style)) {
                continue;
            }

            $css = $style['css'] ?? '';
            $scoped = $style['scoped'] ?? false;
            $selector = $style['selector'] ?? $page->id;

            if ($css === '') {
                continue;
            }

            if ($scoped) {
                $prefix = '#' . $selector;
                $css = preg_replace_callback(
                    '/(^|\})\s*([^{}]+)\s*\{/',
                    static function (array $matches) use ($prefix) {
                        $selectors = array_map('trim', explode(',', $matches[2]));
                        $prefixed = array_map(
                            static fn(string $sel): string => $prefix . ' ' . $sel,
                            $selectors
                        );

                        return $matches[1] . ' ' . implode(', ', $prefixed) . ' {';
                    },
                    $css
                ) ?? $css;
            }

            $cssOutput[] = $css;
        }

        return implode("\n", $cssOutput);
    }
}
