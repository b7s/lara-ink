<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\ComponentService;

use Illuminate\Support\Facades\File;

final class ComponentDiscovery
{
    /**
     * @var array<string, string>
     */
    private array $componentCache = [];

    /**
     * Discover all components in the components directory
     * 
     * @return array<string, string> Component name => file path
     */
    public function discoverComponents(): array
    {
        $componentsPath = ink_resource_path('components');
        
        if (!File::exists($componentsPath)) {
            return [];
        }

        $files = File::allFiles($componentsPath);
        $components = [];

        foreach ($files as $file) {
            $relativePath = str_replace($componentsPath . DIRECTORY_SEPARATOR, '', $file->getPathname());

            if (!str_ends_with($relativePath, '.php') && !str_ends_with($relativePath, '.blade.php')) {
                continue;
            }

            $componentName = $this->normalizeComponentName($relativePath);

            if ($componentName !== null) {
                $components[$componentName] = [
                    'path' => $file->getPathname(),
                    'extension' => $file->getExtension(),
                ];
            }
        }

        $this->componentCache = $components;

        return $components;
    }

    private function normalizeComponentName(string $relativePath): ?string
    {
        $normalizedPath = str_replace(['\\', '/'], '.', $relativePath);

        if (str_ends_with($normalizedPath, '.blade.php')) {
            return substr($normalizedPath, 0, -10);
        }

        if (str_ends_with($normalizedPath, '.php')) {
            return substr($normalizedPath, 0, -4);
        }

        return null;
    }

    public function componentExists(string $name): bool
    {
        if (empty($this->componentCache)) {
            $this->discoverComponents();
        }

        return isset($this->componentCache[$name]);
    }

    /**
     * @return array<string, string>
     */
    public function getDiscoveredComponents(): array
    {
        if (empty($this->componentCache)) {
            $this->discoverComponents();
        }

        return $this->componentCache;
    }

    /**
     * @return array<string, string>
     */
    public function getComponentCache(): array
    {
        return $this->componentCache;
    }
}
