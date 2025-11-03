<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

use B7s\LaraInk\Services\LayoutService\LayoutRenderer;
use B7s\LaraInk\Services\LayoutService\LayoutResolver;

final class LayoutService
{
    public function __construct(
        private readonly LayoutResolver $layoutResolver,
        private readonly LayoutRenderer $layoutRenderer,
    ) {}

    public function resolve(string $layoutName): string
    {
        return $this->layoutResolver->resolve($layoutName);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function applyLayout(string $layoutContent, string $pageContent, array $data = []): string
    {
        return $this->layoutRenderer->applyLayout($layoutContent, $pageContent, $data);
    }


    /**
     * @return array<string>
     */
    public function getAllLayouts(): array
    {
        return $this->layoutResolver->getAllLayouts();
    }
}
