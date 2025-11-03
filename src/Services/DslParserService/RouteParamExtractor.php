<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\DslParserService;

final class RouteParamExtractor
{
    /**
     * @return array<string, mixed>
     */
    public function extractRouteParams(string $slug): array
    {
        $params = [];
        
        if (preg_match_all('/\[([^\]]+)\]/', $slug, $matches)) {
            foreach ($matches[1] as $param) {
                $params[$param] = null;
            }
        }

        return $params;
    }
}
