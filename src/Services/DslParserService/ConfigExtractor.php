<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\DslParserService;

use B7s\LaraInk\DTOs\PageConfig;

final class ConfigExtractor
{
    public function extractConfig(string $content): PageConfig
    {
        $cache = null;
        $layout = null;
        $title = null;
        $auth = false;
        $middleware = null;
        $seo = null;

        if (preg_match('/->cache\((\d+)\)/', $content, $matches)) {
            $cache = (int) $matches[1];
        } elseif (preg_match('/->cache\(true\)/', $content)) {
            $cache = (int) ink_config('cache.ttl', 300);
        } elseif (preg_match('/->cache\(false\)/', $content)) {
            $cache = null;
        }

        if (preg_match('/->layout\([\'"]([^\'"]+)[\'"]\)/', $content, $matches)) {
            $layout = $matches[1];
        }

        if (preg_match('/->title\([\'"]([^\'"]+)[\'"]\)/', $content, $matches)) {
            $title = $matches[1];
        }

        if (preg_match('/->auth\(true\)/', $content)) {
            $auth = true;
        }

        // Extract middleware - supports both string and array syntax
        if (preg_match('/->middleware\(\[([^\]]+)\]\)/', $content, $matches)) {
            // Array syntax: ->middleware(['auth', 'verified'])
            $middlewareString = $matches[1];
            $middleware = array_map(
                fn($item) => trim($item, " '\"\t\n\r\0\x0B"),
                explode(',', $middlewareString)
            );
            $middleware = array_filter($middleware);
        } elseif (preg_match('/->middleware\([\'"]([^\'"]+)[\'"]\)/', $content, $matches)) {
            // String syntax: ->middleware('auth')
            $middleware = [$matches[1]];
        }

        $seo = $this->extractSeoConfig($content);

        return new PageConfig(
            cache: $cache,
            layout: $layout,
            title: $title,
            auth: $auth,
            middleware: $middleware,
            seo: $seo,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function extractSeoConfig(string $content): ?array
    {
        if (!preg_match('/->seo\(/', $content)) {
            return null;
        }

        $seoData = [
            'title' => null,
            'description' => null,
            'keywords' => null,
            'image' => null,
            'canonical' => null,
            'robots' => 'index, follow',
            'meta' => [],
            'og' => [],
            'twitter' => [],
        ];

        if (preg_match('/->seo\((.*)\)/Us', $content, $matches)) {
            $arguments = trim($matches[1]);

            $pattern = '/(?:(?:\'([^\']*)\')|(?:"([^"]*)"))/';
            preg_match_all($pattern, $arguments, $argMatches);

            $stringArguments = array_map(
                fn (int $index) => $argMatches[1][$index] !== '' ? $argMatches[1][$index] : $argMatches[2][$index],
                array_keys($argMatches[0])
            );

            $seoData['title'] = $stringArguments[0] ?? $seoData['title'];
            $seoData['description'] = $stringArguments[1] ?? $seoData['description'];
            $seoData['keywords'] = $stringArguments[2] ?? $seoData['keywords'];
            $seoData['image'] = $stringArguments[3] ?? $seoData['image'];
            $seoData['canonical'] = $stringArguments[4] ?? $seoData['canonical'];
            $seoData['robots'] = $stringArguments[5] ?? $seoData['robots'];
        }

        return $seoData;
    }

    /**
     * @return array{PageConfig, string, string}
     */
    public function extractConfigAndContent(string $content): array
    {
        $lines = explode("\n", $content);
        $configEndLine = 0;
        $inPhpBlock = false;
        
        foreach ($lines as $index => $line) {
            $trimmed = trim($line);
            
            if ($trimmed === '<?php') {
                $inPhpBlock = true;
                continue;
            }
            
            if ($inPhpBlock && $trimmed === '?>') {
                $configEndLine = $index + 1;
                break;
            }
        }
        
        $configBlock = implode("\n", array_slice($lines, 0, $configEndLine));
        $bladeContent = implode("\n", array_slice($lines, $configEndLine));
        
        $config = $this->extractConfig($configBlock);
        
        return [$config, trim($bladeContent), $configBlock];
    }
}
