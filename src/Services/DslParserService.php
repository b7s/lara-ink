<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

use B7s\LaraInk\DTOs\PageConfig;
use B7s\LaraInk\DTOs\ParsedPage;
use Illuminate\Support\Facades\App;

final class DslParserService
{
    /**
     * @param array<string, mixed> $variables
     */
    public function __construct(
        private array $variables = [],
    ) {}

    public function parse(string $filePath): ParsedPage
    {
        $content = file_get_contents($filePath);
        
        if ($content === false) {
            throw new \RuntimeException("Failed to read file: {$filePath}");
        }

        [$config, $bladeContent] = $this->extractConfigAndContent($content);

        $slug = $this->generateSlug($filePath);
        $params = $this->extractRouteParams($slug);
        $translations = $this->extractTranslations($bladeContent);

        return new ParsedPage(
            slug: $slug,
            filePath: $filePath,
            config: $config,
            html: $bladeContent,
            js: '',
            css: '',
            params: $params,
            translations: $translations,
        );
    }

    /**
     * @return array{PageConfig, string}
     */
    private function extractConfigAndContent(string $content): array
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
            
            if ($inPhpBlock && ($trimmed === '?>' || $trimmed === '')) {
                $configEndLine = $index + 1;
                break;
            }
        }
        
        $configBlock = implode("\n", array_slice($lines, 0, $configEndLine));
        $bladeContent = implode("\n", array_slice($lines, $configEndLine));
        
        $config = $this->extractConfig($configBlock);
        
        return [$config, trim($bladeContent)];
    }

    private function extractConfig(string $content): PageConfig
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

        if (preg_match('/->middleware\([\'"]([^\'"]+)[\'"]\)/', $content, $matches)) {
            $middleware = $matches[1];
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
    private function extractSeoConfig(string $content): ?array
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

    private function extractSection(string $content, string $section): string
    {
        $pattern = "/<<<{$section}\s*(.*?)\s*{$section};/s";
        
        if (preg_match($pattern, $content, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    private function extractVariables(string $content): void
    {
        if (preg_match_all('/\$(\w+)\s*=\s*([^;]+);/', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $varName = $match[1];
                $varValue = trim($match[2], '\'"');
                $this->variables[$varName] = $varValue;
            }
        }
    }

    private function generateSlug(string $filePath): string
    {
        $basePath = App::basePath('resources/lara-ink/pages/');
        $relativePath = str_replace($basePath, '', $filePath);
        $relativePath = str_replace('.php', '', $relativePath);
        
        return '/' . ltrim($relativePath, '/');
    }

    /**
     * @return array<string, mixed>
     */
    private function extractRouteParams(string $slug): array
    {
        $params = [];
        
        if (preg_match_all('/\[([^\]]+)\]/', $slug, $matches)) {
            foreach ($matches[1] as $param) {
                $params[$param] = null;
            }
        }

        return $params;
    }

    /**
     * @return array<string>
     */
    private function extractTranslations(string $content): array
    {
        $translations = [];
        
        $patterns = [
            '/__\([\'"]([^\'"]+)[\'"]\)/',
            '/trans\([\'"]([^\'"]+)[\'"]\)/',
            '/trans_choice\([\'"]([^\'"]+)[\'"]\)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                $translations = array_merge($translations, $matches[1]);
            }
        }

        return array_unique($translations);
    }

    /**
     * @return array<string, mixed>
     */
    public function getVariables(): array
    {
        return $this->variables;
    }
}
