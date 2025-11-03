<?php

declare(strict_types=1);

namespace B7s\LaraInk\DTOs;

final class PageVariable
{
    public function __construct(
        public readonly string $name,
        public readonly mixed $value,
        public readonly string $type,
        public readonly string $alpineVarName,
    ) {}

    public function toJson(): string
    {
        return json_encode($this->value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
