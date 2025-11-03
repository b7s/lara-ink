<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;

final class TranslationService
{
    /**
     * @var array<int, string>
     */
    private const TRANSLATION_PATTERNS = [
        '/__\([\'\"]([^\'\"]+)[\'\"]\)/',
        '/trans\([\'\"]([^\'\"]+)[\'\"]\)/',
        '/trans_choice\([\'\"]([^\'\"]+)[\'\"]\)/',
        '/@lang\([\'\"]([^\'\"]+)[\'\"]\)/',
    ];

    /**
     * @var array<string>
     */
    private array $collectedKeys = [];

    /**
     * @param array<string> $keys
     */
    public function collectKeys(array $keys): void
    {
        if ($keys === []) {
            return;
        }

        $this->collectedKeys = array_unique(array_merge($this->collectedKeys, $keys));
    }

    public function collectKeysFromContent(string $content): void
    {
        $keys = self::extractKeysFromContent($content);

        if ($keys !== []) {
            $this->collectKeys($keys);
        }
    }

    /**
     * @return array<string>
     */
    public static function extractKeysFromContent(string $content): array
    {
        $keys = [];

        foreach (self::TRANSLATION_PATTERNS as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $keys = array_merge($keys, $matches[1]);
            }
        }

        return array_unique($keys);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function generateTranslations(): array
    {
        $translations = [];
        $locales = $this->getAvailableLocales();

        foreach ($locales as $locale) {
            $laravelLocale = $this->normalizeLocale($locale);
            $translations[$locale] = [];
            
            foreach ($this->collectedKeys as $key) {
                $translation = Lang::get($key, [], $laravelLocale);
                // Only add if translation was found (not the key itself)
                if ($translation !== $key) {
                    $translations[$locale][$key] = $translation;
                }
            }
        }

        return $translations;
    }

    public function translate(string $key, ?string $locale = null): string
    {
        $candidateLocales = $this->resolveLocaleFallbacks($locale);

        foreach ($candidateLocales as $candidate) {
            $translation = Lang::get($key, [], $candidate);

            if (is_string($translation) && $translation !== $key) {
                return $translation;
            }
        }

        return $key;
    }

    public function generateJsFile(string $outputPath): void
    {
        $translations = $this->generateTranslations();
        $json = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $js = <<<JS
// LaraInk Translations
// Generated at: {$this->getCurrentTimestamp()}
// Collected keys: {$this->getCollectedKeysCount()}

window.lara_ink = window.lara_ink || {};
window.lara_ink.translations = {$json};
JS;

        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, $js);
    }

    private function getCurrentTimestamp(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function getCollectedKeysCount(): int
    {
        return count($this->collectedKeys);
    }

    /**
     * @return array<string>
     */
    private function getAvailableLocales(): array
    {
        $langPath = App::langPath();
        
        if (!File::exists($langPath)) {
            return [str_replace('_', '-', App::getLocale())];
        }

        $locales = [];
        $directories = File::directories($langPath);

        foreach ($directories as $directory) {
            $locale = basename($directory);
            // Convert pt_BR format to pt-BR for consistency
            $locales[] = str_replace('_', '-', $locale);
        }

        // If no locales found, use current locale
        if (empty($locales)) {
            $locales[] = str_replace('_', '-', App::getLocale());
        }

        return $locales;
    }

    private function normalizeLocale(?string $locale = null): string
    {
        $locale = $locale ?? App::getLocale();

        return str_replace('-', '_', $locale);
    }

    /**
     * @return array<string>
     */
    private function resolveLocaleFallbacks(?string $requested = null): array
    {
        $requestedNormalized = str_replace('-', '_', $requested ?? App::getLocale());
        $availableLocales = $this->getAvailableLocales();
        $availableNormalized = array_map(fn (string $locale) => str_replace('-', '_', $locale), $availableLocales);

        $candidates = [];

        // 1. Exact match for requested locale
        if (in_array($requestedNormalized, $availableNormalized, true)) {
            $candidates[] = $requestedNormalized;
        }

        // 2. Match by base language (e.g., en -> en_US)
        $baseLanguage = strtok($requestedNormalized, '_');
        foreach ($availableNormalized as $locale) {
            if (strtok($locale, '_') === $baseLanguage && !in_array($locale, $candidates, true)) {
                $candidates[] = $locale;
            }
        }

        // 3. Default to en_US / en if available
        foreach (['en_US', 'en'] as $fallback) {
            if (in_array($fallback, $availableNormalized, true) && !in_array($fallback, $candidates, true)) {
                $candidates[] = $fallback;
            }
        }

        // 4. Add all other available locales to ensure something renders
        foreach ($availableNormalized as $locale) {
            if (!in_array($locale, $candidates, true)) {
                $candidates[] = $locale;
            }
        }

        return $candidates;
    }

    /**
     * @return array<string>
     */
    public function getCollectedKeys(): array
    {
        return $this->collectedKeys;
    }
}
