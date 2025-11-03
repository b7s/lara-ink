<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\CompilerService;

use B7s\LaraInk\DTOs\ParsedPage;
use B7s\LaraInk\Services\BladeCompilerService;

final class HtmlCompiler
{
    public function __construct(
        private readonly BladeCompilerService $bladeCompiler,
        private readonly ComponentProcessor $componentProcessor,
        private readonly TranslationTransformer $translationTransformer,
        private readonly VariableTransformer $variableTransformer,
    ) {}

    public function compile(ParsedPage $page): string
    {
        $html = $page->html;
        $context = $this->variableTransformer->extractVariableContext($page);

        // Process components FIRST (before Blade) so they can be included in the compilation
        $html = $this->componentProcessor->processComponents($html, $page->id, $context);

        // Transform translations before compiling Blade directives
        $html = $this->translationTransformer->transform($html);

        // Compile Blade directives (loops, conditionals, etc.)
        $html = $this->bladeCompiler->compile($html);

        // Substitute variables after compilation so attributes are already normalized
        $html = $this->variableTransformer->transformVariables($html, $page);

        // Replace PHP variable references with Alpine variable names after compilation
        $html = $this->variableTransformer->replacePhpVariablesWithAlpine($html, $page);

        return $html;
    }
}
