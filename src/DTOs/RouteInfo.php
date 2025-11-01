<?php

declare(strict_types=1);

namespace B7s\LaraInk\DTOs;

final class RouteInfo
{
    public function __construct(
        public readonly string $url,
        public readonly string $method = 'GET',
    ) {}

    /**
     * @return array{url: string, method: string}
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'method' => strtoupper($this->method),
        ];
    }
}
