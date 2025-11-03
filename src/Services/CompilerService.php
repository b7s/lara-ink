<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

use B7s\LaraInk\DTOs\PageVariable;
use B7s\LaraInk\DTOs\ParsedPage;
use B7s\LaraInk\Services\CompilerService\ComponentProcessor;
use B7s\LaraInk\Services\CompilerService\CssCompiler;
use B7s\LaraInk\Services\CompilerService\HtmlCompiler;
use B7s\LaraInk\Services\CompilerService\JsCompiler;
use B7s\LaraInk\Services\CompilerService\Minifier;
use B7s\LaraInk\Services\CompilerService\TranslationTransformer;
use B7s\LaraInk\Services\CompilerService\VariableTransformer;

final class CompilerService
{
    public function __construct(
        private readonly HtmlCompiler $htmlCompiler,
        private readonly JsCompiler $jsCompiler,
        private readonly CssCompiler $cssCompiler,
        private readonly VariableTransformer $variableTransformer,
        private readonly Minifier $minifier,
    ) {}

    public function compileHtml(ParsedPage $page): string
    {
        return $this->htmlCompiler->compile($page);
    }


    public function generateAlpineVariablesInit(ParsedPage $page): string
    {
        return $this->variableTransformer->generateAlpineVariablesInit($page);
    }

    public function compileCss(ParsedPage $page, array $variables): string
    {
        return $this->cssCompiler->compile($page, $variables);
    }

    public function compileJs(ParsedPage $page): string
    {
        return $this->jsCompiler->compile($page);
    }

    /**
     * Get page variables for use in templates
     * 
     * @param ParsedPage $page
     * @return array<string, PageVariable>
     */
    public function getPageVariables(ParsedPage $page): array
    {
        return $page->variables;
    }

    /**
     * Minify HTML to reduce file size
     */
    public function minifyHtml(string $html): string
    {
        return $this->minifier->minifyHtml($html);
    }
}
