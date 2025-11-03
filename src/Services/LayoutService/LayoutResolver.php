<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\LayoutService;

use Illuminate\Support\Facades\File;

final class LayoutResolver
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

    public function findLayout(string $layoutName): ?string
    {
        $basePath = rtrim(ink_resource_path('layouts'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
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
        $basePath = rtrim(ink_resource_path('layouts'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        
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
