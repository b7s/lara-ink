<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

use B7s\LaraInk\Services\ComponentService\AttributeParser;
use B7s\LaraInk\Services\ComponentService\ComponentCompiler;
use B7s\LaraInk\Services\ComponentService\ComponentDiscovery;
use B7s\LaraInk\Services\ComponentService\ComponentLoader;

final class ComponentService
{
    public function __construct(
        private readonly ComponentDiscovery $componentDiscovery,
        private readonly ComponentLoader $componentLoader,
        private readonly AttributeParser $attributeParser,
        private readonly ComponentCompiler $componentCompiler,
    ) {}

    /**
     * Discover all components in the components directory
     * 
     * @return array<string, string> Component name => file path
     */
    public function discoverComponents(): array
    {
        return $this->componentDiscovery->discoverComponents();
    }

    /**
     * Load a component by name
     */
    public function loadComponent(string $name): ?string
    {
        return $this->componentLoader->loadComponent($name);
    }

    /**
     * Generate unique component ID
     */
    public function generateComponentId(string $componentName, string $parentId = ''): string
    {
        return $this->componentLoader->generateComponentId($componentName, $parentId);
    }

    /**
     * Parse component attributes from tag
     * 
     * @return array{props: array<string, mixed>, lazy: bool, slots: array<string, string>}
     */
    public function parseComponentAttributes(string $tag): array
    {
        return $this->attributeParser->parseComponentAttributes($tag);
    }

    /**
     * Compile component to Alpine.js structure
     */
    public function compileComponent(
        string $componentContent,
        array $props,
        string $componentId,
        bool $lazy = false
    ): string {
        return $this->componentCompiler->compileComponent($componentContent, $props, $componentId, $lazy);
    }

    /**
     * Check if component exists
     */
    public function componentExists(string $name): bool
    {
        return $this->componentDiscovery->componentExists($name);
    }

    /**
     * Get all discovered components (for debugging)
     * 
     * @return array<string, string>
     */
    public function getDiscoveredComponents(): array
    {
        return $this->componentDiscovery->getDiscoveredComponents();
    }
}
