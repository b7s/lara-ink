<?php

declare(strict_types=1);

namespace B7s\LaraInk\DTOs;

final class ParsedPage
{
    /**
     * @param array<string, mixed> $params
     * @param array<string> $translations
     * @param array<string, PageVariable> $variables
     */
    public function __construct(
        public readonly string $id,
        public readonly string $slug,
        public readonly string $filePath,
        public readonly PageConfig $config,
        public readonly string $html,
        public readonly string $js,
        public readonly string $css,
        public readonly array $params,
        public readonly array $translations,
        public readonly array $variables = [],
    ) {}
}
