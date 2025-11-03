<?php

declare(strict_types=1);

namespace B7s\LaraInk\DTOs;

final class PageConfig
{
    /**
     * @param array<string, mixed>|null $seo SEO configuration array from SeoConfig::toArray()
     * @param array<int, string>|null $middleware Array of middleware names
     */
    public function __construct(
        public readonly ?int $cache = null,
        public readonly ?string $layout = null,
        public readonly ?string $title = null,
        public readonly bool $auth = false,
        public readonly ?array $middleware = null,
        public readonly ?array $seo = null,
    ) {}

    public function getCacheTtl(): int
    {
        return $this->cache ?? (int) ink_config('cache.ttl', 300);
    }

    public function getLayout(): string
    {
        return $this->layout ?? (string) ink_config('default_layout', 'app');
    }

    /**
     * Get the SEO configuration as an array
     * 
     * @return array<string, mixed>|null
     */
    public function getSeoConfig(): ?array
    {
        return $this->seo;
    }

    public function requiresAuth(): bool
    {
        return $this->auth || $this->middleware !== null;
    }
}
