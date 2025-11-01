<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;

final class TranslationService
{
    /**
     * @var array<string>
     */
    private array $collectedKeys = [];

    /**
     * @param array<string> $keys
     */
    public function collectKeys(array $keys): void
    {
        $this->collectedKeys = array_unique(array_merge($this->collectedKeys, $keys));
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function generateTranslations(): array
    {
        $translations = [];
        $locales = $this->getAvailableLocales();

        foreach ($locales as $locale) {
            $translations[$locale] = [];
            
            foreach ($this->collectedKeys as $key) {
                $translations[$locale][$key] = Lang::get($key, [], $locale);
            }
        }

        return $translations;
    }

    public function generateJsFile(string $outputPath): void
    {
        $translations = $this->generateTranslations();
        $json = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $js = <<<JS
window.lara_ink_translations = {$json};
JS;

        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, $js);
    }

    /**
     * @return array<string>
     */
    private function getAvailableLocales(): array
    {
        $langPath = App::langPath();
        
        if (!File::exists($langPath)) {
            return ['en'];
        }

        $locales = [];
        $directories = File::directories($langPath);

        foreach ($directories as $directory) {
            $locales[] = basename($directory);
        }

        return empty($locales) ? ['en'] : $locales;
    }

    /**
     * @return array<string>
     */
    public function getCollectedKeys(): array
    {
        return $this->collectedKeys;
    }
}
