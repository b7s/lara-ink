<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\DslParserService;

final class SlugGenerator
{
    public function generateSlug(string $filePath): string
    {
        $basePath = rtrim(ink_resource_path('pages'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $relativePath = str_replace($basePath, '', $filePath);
        
        // Remove .blade.php or .php extension
        if (str_ends_with($relativePath, '.blade.php')) {
            $relativePath = str_replace('.blade.php', '', $relativePath);
        } else {
            $relativePath = str_replace('.php', '', $relativePath);
        }
        
        return '/' . ltrim($relativePath, '/');
    }

    public function generatePageId(string $filePath): string
    {
        $hash = substr(sha1($filePath), 0, 12);

        return 'page-' . $hash;
    }
}
