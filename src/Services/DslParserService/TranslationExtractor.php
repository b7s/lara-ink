<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\DslParserService;

use B7s\LaraInk\Services\TranslationService;

final class TranslationExtractor
{
    /**
     * @return array<string>
     */
    public function extractTranslations(string $bladeContent, string $phpBlock): array
    {
        $bladeKeys = TranslationService::extractKeysFromContent($bladeContent);
        $configKeys = TranslationService::extractKeysFromContent($phpBlock);

        return array_values(array_unique(array_merge($bladeKeys, $configKeys)));
    }
}
