<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\CompilerService;

final class TranslationTransformer
{
    public function transform(string $content): string
    {
        // First convert Blade echo statements directly into Alpine bindings
        $echoPatterns = [
            '/\{\{\s*__\(\s*[\'\"]([^\'\"]+)[\'\"]\s*\)\s*\}\}/' => '<span x-text="lara_ink.trans(\'$1\')"></span>',
            '/\{\{\s*trans\(\s*[\'\"]([^\'\"]+)[\'\"]\s*\)\s*\}\}/' => '<span x-text="lara_ink.trans(\'$1\')"></span>',
            '/\{\{\s*@lang\(\s*[\'\"]([^\'\"]+)[\'\"]\s*\)\s*\}\}/' => '<span x-text="lara_ink.trans(\'$1\')"></span>',
            '/\{!!\s*__\(\s*[\'\"]([^\'\"]+)[\'\"]\s*\)\s*!!\}/' => '<span x-html="lara_ink.trans(\'$1\')"></span>',
            '/\{!!\s*trans\(\s*[\'\"]([^\'\"]+)[\'\"]\s*\)\s*!!\}/' => '<span x-html="lara_ink.trans(\'$1\')"></span>',
            '/\{!!\s*@lang\(\s*[\'\"]([^\'\"]+)[\'\"]\s*\)\s*!!\}/' => '<span x-html="lara_ink.trans(\'$1\')"></span>',
        ];

        foreach ($echoPatterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        // Replace any remaining translation helper calls with JS helpers
        $helperPatterns = [
            '/__\([\'\"]([^\'\"]+)[\'\"]\)/' => 'lara_ink.trans(\'$1\')',
            '/trans\([\'\"]([^\'\"]+)[\'\"]\)/' => 'lara_ink.trans(\'$1\')',
            '/@lang\([\'\"]([^\'\"]+)[\'\"]\)/' => 'lara_ink.trans(\'$1\')',
        ];

        foreach ($helperPatterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        // Catch any remaining Blade echos using the JS helper
        $content = preg_replace(
            '/\{\{\s*lara_ink\.trans\(([^}]+)\)\s*\}\}/',
            '<span x-text="lara_ink.trans($1)"></span>',
            $content
        );

        return $content;
    }

    public function transformApiCalls(string $js): string
    {
        return preg_replace(
            '/@(\w+)\((.*?)\)/',
            'await lara_ink.newReq(\'$1\', $2)',
            $js
        );
    }
}
