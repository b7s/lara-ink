<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

final class CacheService
{
    /**
     * @var array<string, int>
     */
    private array $pageCacheTtls = [];

    public function registerPageCache(string $slug, int $ttl): void
    {
        $this->pageCacheTtls[$slug] = $ttl;
    }

    public function getPageCacheTtl(string $slug): ?int
    {
        return $this->pageCacheTtls[$slug] ?? null;
    }

    public function shouldCache(string $slug, ?bool $cache = null): bool
    {
        if ($cache === false || ($cache === null && !ink_config('cache.enable', false))) {
            return false;
        }

        return isset($this->pageCacheTtls[$slug]);
    }

    /**
     * @return array<string, int>
     */
    public function getAllPageCaches(): array
    {
        return $this->pageCacheTtls;
    }

    public function generateCacheManifest(): string
    {
        $manifest = [];

        foreach ($this->pageCacheTtls as $slug => $ttl) {
            $manifest[$slug] = [
                'ttl' => $ttl,
                'enabled' => true,
            ];
        }

        return json_encode($manifest, JSON_PRETTY_PRINT);
    }
}
