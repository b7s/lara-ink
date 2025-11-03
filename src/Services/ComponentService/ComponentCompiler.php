<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\ComponentService;

final class ComponentCompiler
{
    public function __construct(
        private readonly ElementExtractor $elementExtractor,
    ) {}

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
            
            $wrapperAttributes = $this->elementExtractor->renderAttributes($attributeMap);
            
            return sprintf('<div%s><template x-if="loaded"><div x-html="content"></div></template></div>', $wrapperAttributes);
        }
        
        // Non-lazy components: render immediately
        $attributeMap = [
            'id' => $componentId,
            'x-data' => "{ componentId: '{$componentId}', props: {$propsJson} }",
        ];

        $singleRoot = $this->elementExtractor->extractSingleRootElement($componentContent);

        if ($singleRoot !== null) {
            $mergedAttributes = $this->elementExtractor->mergeAttributes($singleRoot['attributes'], $attributeMap);

            return match ($singleRoot['closing']) {
                'void' => sprintf('<%s%s>', $singleRoot['tag'], $mergedAttributes),
                'self-closing' => sprintf('<%s%s />', $singleRoot['tag'], $mergedAttributes),
                default => sprintf('<%s%s>%s</%s>', $singleRoot['tag'], $mergedAttributes, $singleRoot['content'], $singleRoot['tag']),
            };
        }

        $wrapperAttributes = $this->elementExtractor->renderAttributes($attributeMap);

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
}
