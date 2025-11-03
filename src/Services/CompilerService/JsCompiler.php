<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\CompilerService;

use B7s\LaraInk\DTOs\ParsedPage;

final class JsCompiler
{
    public function __construct(
        private readonly TranslationTransformer $translationTransformer,
    ) {}

    public function compile(ParsedPage $page): string
    {
        $js = $page->js;

        $js = $this->translationTransformer->transform($js);
        $js = $this->translationTransformer->transformApiCalls($js);

        return $js;
    }
}
