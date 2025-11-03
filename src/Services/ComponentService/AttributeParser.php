<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\ComponentService;

final class AttributeParser
{
    /**
     * Parse component attributes from tag
     * 
     * @return array{props: array<string, mixed>, lazy: bool, slots: array<string, string>}
     */
    public function parseComponentAttributes(string $tag): array
    {
        $props = [];
        $lazy = false;
        $slots = [];

        // Check for lazy attribute
        if (preg_match('/\blazy\b/', $tag)) {
            $lazy = true;
        }

        // Extract attributes: name="value" or :name="value" (double or single quoted)
        preg_match_all('/(:?)([A-Za-z_][\w-]*)\s*=\s*("[^"]*"|\'[^\']*\')/s', $tag, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $isBinding = $match[1] === ':';
            $attrName = $match[2];
            $rawValue = $match[3];
            $attrValue = substr($rawValue, 1, -1); // remove surrounding quotes

            if ($isBinding) {
                $props[$attrName] = ['type' => 'binding', 'value' => $attrValue];
            } else {
                $props[$attrName] = ['type' => 'static', 'value' => $attrValue];
            }
        }

        // Extract standalone attributes (e.g., lazy, disabled)
        preg_match_all('/\s(\w+)(?=\s|>|\/|$)/', $tag, $standaloneMatches);
        
        foreach ($standaloneMatches[1] as $attr) {
            if (!isset($props[$attr]) && $attr !== 'lazy') {
                $props[$attr] = ['type' => 'boolean', 'value' => true];
            }
        }

        return [
            'props' => $props,
            'lazy' => $lazy,
            'slots' => $slots,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function extractSlots(string $content): array
    {
        $slots = [];

        // Extract named slots: <x-slot:name>...</x-slot>
        preg_match_all('/<x-slot:(\w+)>(.*?)<\/x-slot>/s', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $slotName = $match[1];
            $slotContent = $match[2];
            $slots[$slotName] = trim($slotContent);
        }

        // Extract default slot (everything not in named slots)
        $contentWithoutSlots = preg_replace('/<x-slot:\w+>.*?<\/x-slot>/s', '', $content);
        
        if (trim($contentWithoutSlots ?? '') !== '') {
            $slots['default'] = trim($contentWithoutSlots);
        }

        return $slots;
    }
}
