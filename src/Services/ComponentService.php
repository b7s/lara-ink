<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services;

use Illuminate\Support\Facades\File;

final class ComponentService
{
    private const VOID_ELEMENTS = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link',
        'meta', 'param', 'source', 'track', 'wbr', 'keygen', 'command'
    ];

    /**
     * @var array<string, string>
     */
    private array $componentCache = [];

    /**
     * Discover all components in the components directory
     * 
     * @return array<string, string> Component name => file path
     */
    public function discoverComponents(): array
    {
        $componentsPath = ink_resource_path('components');
        
        if (!File::exists($componentsPath)) {
            return [];
        }

        $files = File::allFiles($componentsPath);
        $components = [];

        foreach ($files as $file) {
            $relativePath = str_replace($componentsPath . DIRECTORY_SEPARATOR, '', $file->getPathname());

            if (!str_ends_with($relativePath, '.php') && !str_ends_with($relativePath, '.blade.php')) {
                continue;
            }

            $componentName = $this->normalizeComponentName($relativePath);

            if ($componentName !== null) {
                $components[$componentName] = [
                    'path' => $file->getPathname(),
                    'extension' => $file->getExtension(),
                ];
            }
        }

        $this->componentCache = $components;

        return $components;
    }

    private function normalizeComponentName(string $relativePath): ?string
    {
        $normalizedPath = str_replace(['\\', '/'], '.', $relativePath);

        if (str_ends_with($normalizedPath, '.blade.php')) {
            return substr($normalizedPath, 0, -10);
        }

        if (str_ends_with($normalizedPath, '.php')) {
            return substr($normalizedPath, 0, -4);
        }

        return null;
    }

    /**
     * Load a component by name
     */
    public function loadComponent(string $name): ?string
    {
        if (empty($this->componentCache)) {
            $this->discoverComponents();
        }

        $componentMeta = $this->componentCache[$name] ?? null;

        if ($componentMeta === null) {
            return null;
        }

        $filePath = $componentMeta['path'];

        if (!File::exists($filePath)) {
            return null;
        }

        return File::get($filePath);
    }

    /**
     * Generate unique component ID
     */
    public function generateComponentId(string $componentName, string $parentId = ''): string
    {
        $hash = substr(sha1($componentName . $parentId . microtime()), 0, 8);

        return 'cmp-' . $hash;
    }

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
     * Compile component to Alpine.js structure
     */
    public function compileComponent(
        string $componentContent,
        array $props,
        string $componentId,
        bool $lazy = false
    ): string {
        // Build Alpine component data
        $propsJson = $this->buildPropsJson($props);
        
        if ($lazy) {
            // For lazy components, create a placeholder that loads content on intersection
            $contentEncoded = htmlspecialchars($componentContent, ENT_QUOTES, 'UTF-8');
            $attributeMap = [
                'id' => $componentId,
                'x-data' => "{ componentId: '{$componentId}', props: {$propsJson}, loaded: false, content: '' }",
                'x-intersect.margin.50px' => "if (!loaded) { loaded = true; content = atob('" . base64_encode($componentContent) . "'); \$nextTick(() => { Alpine.initTree(\$el); }); }",
                'data-lazy-component' => 'true',
            ];
            
            $wrapperAttributes = $this->renderAttributes($attributeMap);
            
            return sprintf('<div%s><template x-if="loaded"><div x-html="content"></div></template></div>', $wrapperAttributes);
        }
        
        // Non-lazy components: render immediately
        $attributeMap = [
            'id' => $componentId,
            'x-data' => "{ componentId: '{$componentId}', props: {$propsJson} }",
        ];

        $singleRoot = $this->extractSingleRootElement($componentContent);

        if ($singleRoot !== null) {
            $mergedAttributes = $this->mergeAttributes($singleRoot['attributes'], $attributeMap);

            return match ($singleRoot['closing']) {
                'void' => sprintf('<%s%s>', $singleRoot['tag'], $mergedAttributes),
                'self-closing' => sprintf('<%s%s />', $singleRoot['tag'], $mergedAttributes),
                default => sprintf('<%s%s>%s</%s>', $singleRoot['tag'], $mergedAttributes, $singleRoot['content'], $singleRoot['tag']),
            };
        }

        $wrapperAttributes = $this->renderAttributes($attributeMap);

        return sprintf('<div%s>%s</div>', $wrapperAttributes, $componentContent);
    }

    /**
     * @param array<string, mixed> $props
     */
    private function buildPropsJson(array $props): string
    {
        $propsData = [];

        foreach ($props as $name => $value) {
            $propsData[$name] = $value;
        }

        return json_encode($propsData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array<string, string>
     */
    private function extractSlots(string $content): array
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

    /**
     * Check if component exists
     */
    public function componentExists(string $name): bool
    {
        if (empty($this->componentCache)) {
            $this->discoverComponents();
        }

        return isset($this->componentCache[$name]);
    }

    /**
     * Get all discovered components (for debugging)
     * 
     * @return array<string, string>
     */
    public function getDiscoveredComponents(): array
    {
        if (empty($this->componentCache)) {
            $this->discoverComponents();
        }

        return $this->componentCache;
    }

    /**
     * @return array{tag: string, attributes: string, content: string, closing: string}|null
     */
    private function extractSingleRootElement(string $content): ?array
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
    private function mergeAttributes(string $existingAttributes, array $newAttributes): string
    {
        $attrs = $existingAttributes;

        foreach (array_keys($newAttributes) as $name) {
            $pattern = sprintf('/\s%s\s*=\s*(["\"]).*?\1/i', preg_quote($name, '/'));
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
    private function renderAttributes(array $attributes): string
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
