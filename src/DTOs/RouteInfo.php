<?php

declare(strict_types=1);

namespace B7s\LaraInk\DTOs;

final class RouteInfo
{
    public function __construct(
        public readonly string $url,
        public readonly string $method = 'GET',
        public readonly string $type = 'lara-ink',
    ) {}

    public function __toString(): string
    {
        return $this->url;
    }

    /**
     * @return array{url: string, method: string}
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'method' => strtoupper($this->method),
            'type' => $this->type,
        ];
    }
}
