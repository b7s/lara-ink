<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\ComponentService;

use Illuminate\Support\Facades\File;

final class ComponentLoader
{
    public function __construct(
        private readonly ComponentDiscovery $componentDiscovery,
    ) {}

    /**
     * Load a component by name
     */
    public function loadComponent(string $name): ?string
    {
        $componentCache = $this->componentDiscovery->getComponentCache();

        if (empty($componentCache)) {
            $this->componentDiscovery->discoverComponents();
            $componentCache = $this->componentDiscovery->getComponentCache();
        }

        $componentMeta = $componentCache[$name] ?? null;

        if ($componentMeta === null) {
            return null;
        }

        $filePath = $componentMeta['path'];

        if (!File::exists($filePath)) {
            return null;
        }

        return File::get($filePath);
    }

    /**
     * Generate unique component ID
     */
    public function generateComponentId(string $componentName, string $parentId = ''): string
    {
        $hash = substr(sha1($componentName . $parentId . microtime()), 0, 8);

        return 'cmp-' . $hash;
    }
}
