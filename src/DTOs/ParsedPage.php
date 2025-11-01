<?php

declare(strict_types=1);

namespace B7s\LaraInk\DTOs;

final class ParsedPage
{
    /**
     * @param array<string, mixed> $params
     * @param array<string> $translations
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $filePath,
        public readonly PageConfig $config,
        public readonly string $html,
        public readonly string $js,
        public readonly string $css,
        public readonly array $params,
        public readonly array $translations,
    ) {}
}
