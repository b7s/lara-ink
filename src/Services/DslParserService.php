<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

use B7s\LaraInk\DTOs\ParsedPage;
use B7s\LaraInk\Services\DslParserService\ConfigExtractor;
use B7s\LaraInk\Services\DslParserService\RouteParamExtractor;
use B7s\LaraInk\Services\DslParserService\SlugGenerator;
use B7s\LaraInk\Services\DslParserService\TranslationExtractor;
use B7s\LaraInk\Services\DslParserService\VariableExtractor;

final class DslParserService
{
    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(
        private readonly ConfigExtractor $configExtractor,
        private readonly VariableExtractor $variableExtractor,
        private readonly SlugGenerator $slugGenerator,
        private readonly RouteParamExtractor $routeParamExtractor,
        private readonly TranslationExtractor $translationExtractor,
        private array $variables = [],
    ) {}

    public function parse(string $filePath): ParsedPage
    {
        $content = file_get_contents($filePath);
        
        if ($content === false) {
            throw new \RuntimeException("Failed to read file: {$filePath}");
        }

        [$config, $bladeContent, $phpBlock] = $this->configExtractor->extractConfigAndContent($content);
        $variables = $this->variableExtractor->extractAndProcessVariables($phpBlock, $filePath);

        $slug = $this->slugGenerator->generateSlug($filePath);
        $params = $this->routeParamExtractor->extractRouteParams($slug);
        $translations = $this->translationExtractor->extractTranslations($bladeContent, $phpBlock);

        $pageId = $this->slugGenerator->generatePageId($filePath);

        return new ParsedPage(
            id: $pageId,
            slug: $slug,
            filePath: $filePath,
            config: $config,
            html: $bladeContent,
            js: '',
            css: '',
            params: $params,
            translations: $translations,
            variables: $variables,
        );
    }


    /**
     * @return array<string, mixed>
     */
    public function getVariables(): array
    {
        return $this->variables;
    }
}
