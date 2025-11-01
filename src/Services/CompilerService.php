<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

use B7s\LaraInk\DTOs\ParsedPage;

final class CompilerService
{
    public function __construct(
        private readonly TranslationService $translationService,
        private readonly RouteService $routeService,
        private readonly BladeCompilerService $bladeCompiler,
    ) {}

    public function compileHtml(ParsedPage $page): string
    {
        $html = $page->html;

        $html = $this->bladeCompiler->compile($html);
        $html = $this->transformTranslations($html);
        $html = $this->transformVariables($html, $page);

        return $html;
    }

    public function compileJs(ParsedPage $page): string
    {
        $js = $page->js;

        $js = $this->transformTranslations($js);
        $js = $this->transformApiCalls($js);
        $js = $this->injectRequestObject($js, $page);

        return $js;
    }

    public function compileCss(ParsedPage $page, array $variables): string
    {
        $css = $page->css;

        foreach ($variables as $varName => $varValue) {
            $css = str_replace('$' . $varName, $varValue, $css);
        }

        return $css;
    }

    private function transformPhpToAlpine(string $html): string
    {
        $html = preg_replace('/\{\{\s*\$(\w+)\s*\}\}/', '<span x-text="$1"></span>', $html);
        
        $html = preg_replace_callback(
            '/<\?php\s+if\s*\(\s*\$?(\w+)\s*\)\s*:\s*\?>(.*?)<\?php\s+endif;\s*\?>/s',
            fn($matches) => '<template x-if="' . trim($matches[1]) . '">' . $matches[2] . '</template>',
            $html
        );

        $html = preg_replace_callback(
            '/<\?php\s+foreach\s*\(\s*\$?(\w+)\s+as\s+\$?(\w+)\s*\)\s*:\s*\?>(.*?)<\?php\s+endforeach;\s*\?>/s',
            fn($matches) => '<template x-for="' . trim($matches[2]) . ' in ' . trim($matches[1]) . '">' . $matches[3] . '</template>',
            $html
        );

        $html = preg_replace_callback(
            '/@if\s*\(\s*\$?(\w+)\s*\)(.*?)@endif/s',
            fn($matches) => '<template x-if="' . trim($matches[1]) . '">' . $matches[2] . '</template>',
            $html
        );

        $html = preg_replace_callback(
            '/@foreach\s*\(\s*\$?(\w+)\s+as\s+\$?(\w+)\s*\)(.*?)@endforeach/s',
            fn($matches) => '<template x-for="' . trim($matches[2]) . ' in ' . trim($matches[1]) . '">' . $matches[3] . '</template>',
            $html
        );

        return $html;
    }

    private function transformTranslations(string $content): string
    {
        $patterns = [
            '/__\([\'"]([^\'"]+)[\'"]\)/' => 'lara_ink.trans(\'$1\')',
            '/trans\([\'"]([^\'"]+)[\'"]\)/' => 'lara_ink.trans(\'$1\')',
            '/trans_choice\([\'"]([^\'"]+)[\'"]\)/' => 'lara_ink.trans(\'$1\')',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    private function transformVariables(string $html, ParsedPage $page): string
    {
        foreach ($page->params as $param => $value) {
            $html = str_replace('$' . $param, "request().$param", $html);
        }

        return $html;
    }

    private function transformApiCalls(string $js): string
    {
        $js = preg_replace(
            '/@(\w+)\((.*?)\)/',
            'await lara_ink.newReq(\'$1\', $2)',
            $js
        );

        return $js;
    }

    private function injectRequestObject(string $js, ParsedPage $page): string
    {
        $requestParams = [];
        
        foreach ($page->params as $param => $value) {
            $requestParams[] = "$param: null";
        }

        $requestObject = 'const request = () => ({ ' . implode(', ', $requestParams) . ' });';

        return $requestObject . "\n" . $js;
    }
}
