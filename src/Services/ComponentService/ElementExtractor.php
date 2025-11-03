<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\ComponentService;

final class ElementExtractor
{
    private const VOID_ELEMENTS = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link',
        'meta', 'param', 'source', 'track', 'wbr', 'keygen', 'command'
    ];

    /**
     * @return array{tag: string, attributes: string, content: string, closing: string}|null
     */
    public function extractSingleRootElement(string $content): ?array
    {
        $trimmed = trim($content);

        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^<([a-zA-Z][\w:-]*)([^>]*)\/?>\s*$/s', $trimmed, $selfClosingMatches)) {
            $tag = strtolower($selfClosingMatches[1]);
            $closing = str_ends_with($selfClosingMatches[0], '/>') ? 'self-closing' : (in_array($tag, self::VOID_ELEMENTS, true) ? 'void' : 'standard');

            if ($closing === 'standard') {
                // Not truly self-closing; fall through
            } else {
                return [
                    'tag' => $selfClosingMatches[1],
                    'attributes' => $selfClosingMatches[2],
                    'content' => '',
                    'closing' => $closing,
                ];
            }
        }

        if (preg_match('/^<([a-zA-Z][\w:-]*)([^>]*)>(.*)<\/\1>\s*$/s', $trimmed, $matches)) {
            return [
                'tag' => $matches[1],
                'attributes' => $matches[2],
                'content' => $matches[3],
                'closing' => 'standard',
            ];
        }

        return null;
    }

    /**
     * @param array<string, string> $newAttributes
     */
    public function mergeAttributes(string $existingAttributes, array $newAttributes): string
    {
        $attrs = $existingAttributes;

        foreach (array_keys($newAttributes) as $name) {
            $pattern = sprintf('/\s%s\s*=\s*(["\']).*?\1/i', preg_quote($name, '/'));
            $attrs = preg_replace($pattern, '', $attrs) ?? $attrs;
        }

        $attrs = trim($attrs);
        $renderedNew = trim($this->renderAttributes($newAttributes));
        $combined = trim($attrs . ' ' . $renderedNew);

        return $combined === '' ? '' : ' ' . $combined;
    }

    /**
     * @param array<string, string> $attributes
     */
    public function renderAttributes(array $attributes): string
    {
        if ($attributes === []) {
            return '';
        }

        $parts = [];

        foreach ($attributes as $name => $value) {
            $escapedValue = htmlspecialchars($value, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
            $parts[] = sprintf('%s="%s"', $name, $escapedValue);
        }

        return ' ' . implode(' ', $parts);
    }
}
