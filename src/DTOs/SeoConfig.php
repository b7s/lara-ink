<?php

declare(strict_types=1);

namespace B7s\LaraInk\DTOs;

final class SeoConfig
{
    /**
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $og
     * @param array<string, mixed> $twitter
     */
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly ?string $keywords = null,
        public readonly ?string $image = null,
        public readonly ?string $canonical = null,
        public readonly string $robots = 'index, follow',
        public readonly array $meta = [],
        public readonly array $og = [],
        public readonly array $twitter = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'keywords' => $this->keywords,
            'image' => $this->image,
            'canonical' => $this->canonical,
            'robots' => $this->robots,
            'meta' => $this->meta,
            'og' => $this->og,
            'twitter' => $this->twitter,
        ];
    }
}
