<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\BuildService;

use Illuminate\Support\Facades\File;

final class PageDiscovery
{
    /**
     * @return array<string>
     */
    public function discoverPages(): array
    {
        $pagesPath = ink_resource_path('pages');
        
        if (!File::exists($pagesPath)) {
            return [];
        }

        $files = File::allFiles($pagesPath);
        $pages = [];

        foreach ($files as $file) {
            $extension = $file->getExtension();
            $filename = $file->getFilename();
            
            // Accept .php and .blade.php files
            if ($extension === 'php' || str_ends_with($filename, '.blade.php')) {
                $pages[] = $file->getPathname();
            }
        }

        return $pages;
    }

    public function detectChangeType(string $relativePath): string
    {
        if (str_contains($relativePath, 'resources/lara-ink/pages/')) {
            return 'page';
        }
        if (str_contains($relativePath, 'resources/lara-ink/layouts/')) {
            return 'layout';
        }
        if (str_contains($relativePath, 'resources/lara-ink/components/')) {
            return 'component';
        }
        return 'other';
    }

    /**
     * @return array<string>
     */
    public function getPagesUsingLayout(string $layoutPath): array
    {
        $layoutName = basename($layoutPath, '.php');
        $pages = $this->discoverPages();
        $affectedPages = [];

        foreach ($pages as $pagePath) {
            $content = File::get($pagePath);
            // Check if page uses this layout
            if (preg_match("/->layout\(['\"]" . preg_quote($layoutName, '/') . "['\"]\)/", $content)) {
                $affectedPages[] = $pagePath;
            }
        }

        return $affectedPages;
    }

    /**
     * @return array<string>
     */
    public function getPagesUsingComponent(string $componentPath): array
    {
        // Extract component name from path
        $componentName = str_replace(
            [ink_resource_path('components') . '/', '.php'],
            ['', ''],
            $componentPath
        );
        $componentName = str_replace('/', '.', $componentName);

        $pages = $this->discoverPages();
        $affectedPages = [];

        foreach ($pages as $pagePath) {
            $content = File::get($pagePath);
            // Check if page uses this component
            $pattern = "/<x-" . preg_quote($componentName, '/') . "[\s\/>]/";
            if (preg_match($pattern, $content)) {
                $affectedPages[] = $pagePath;
            }
        }

        return $affectedPages;
    }
}
