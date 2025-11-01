<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

final class LayoutService
{
    public function resolve(string $layoutName): string
    {
        $layoutPath = $this->findLayout($layoutName);

        if ($layoutPath === null) {
            throw new \RuntimeException("Layout not found: {$layoutName}");
        }

        $content = File::get($layoutPath);

        return $content;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function applyLayout(string $layoutContent, string $pageContent, array $data = []): string
    {
        $viewData = array_merge($data, [
            'slot' => $pageContent,
        ]);

        return Blade::render($layoutContent, $viewData);
    }

    private function findLayout(string $layoutName): ?string
    {
        $basePath = App::basePath('resources/lara-ink/layouts/');
        $layoutName = str_replace('.', '/', $layoutName);
        
        $possiblePaths = [
            $basePath . $layoutName . '.php',
            $basePath . $layoutName . '.blade.php',
        ];

        foreach ($possiblePaths as $path) {
            if (File::exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return array<string>
     */
    public function getAllLayouts(): array
    {
        $basePath = App::basePath('resources/lara-ink/layouts/');
        
        if (!File::exists($basePath)) {
            return [];
        }

        $files = File::allFiles($basePath);
        $layouts = [];

        foreach ($files as $file) {
            $relativePath = str_replace($basePath, '', $file->getPathname());
            $relativePath = str_replace('.php', '', $relativePath);
            $relativePath = str_replace('.blade.php', '', $relativePath);
            $layouts[] = str_replace('/', '.', $relativePath);
        }

        return $layouts;
    }
}
