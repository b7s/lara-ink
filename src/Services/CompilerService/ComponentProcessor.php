<?php

declare(strict_types=1);

namespace B7s\LaraInk\Services\CompilerService;

use B7s\LaraInk\Services\BladeCompilerService;
use B7s\LaraInk\Services\ComponentService;

final class ComponentProcessor
{
    public function __construct(
        private readonly ComponentService $componentService,
        private readonly BladeCompilerService $bladeCompiler,
        private readonly TranslationTransformer $translationTransformer,
    ) {}

    /**
     * Process component tags (@include and <x-*>)
     */
    public function processComponents(string $html, string $parentId, array $context): string
    {
        // Process @include directives
        $html = $this->processIncludeDirectives($html, $parentId, $context);

        // Process <x-*> component tags
        $html = $this->processComponentTags($html, $parentId, $context);

        return $html;
    }

    private function processIncludeDirectives(string $html, string $parentId, array $context): string
    {
        return preg_replace_callback(
            '/@include\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*(\[.*?\]))?\s*\)/',
            function (array $matches) use ($parentId, $context): string {
                $componentName = $matches[1];
                $propsArray = $matches[2] ?? '[]';

                $componentContent = $this->componentService->loadComponent($componentName);

                if ($componentContent === null) {
                    return "<!-- Component not found: {$componentName} -->";
                }

                $componentId = $this->componentService->generateComponentId($componentName, $parentId);

                // Parse props from array syntax
                $props = $this->parsePropsFromArray($propsArray, $context);
                $componentContext = $this->mergeComponentContext($context, $props);

                // Recursively process nested components
                $componentContent = $this->processComponents($componentContent, $componentId, $componentContext);
                $componentContent = $this->translationTransformer->transform($componentContent);
                $componentContent = $this->bladeCompiler->compile($componentContent);

                return $this->componentService->compileComponent($componentContent, $props, $componentId, false);
            },
            $html
        );
    }

    private function processComponentTags(string $html, string $parentId, array $context): string
    {
        $iterationLimit = 10;
        $iteration = 0;

        // Keep processing until no more component tags are found (for nested components)
        do {
            $hasComponents = false;
            $iteration++;

            // Process paired tags first: <x-name>...</x-name>
            $newHtml = preg_replace_callback(
                '/<x-([A-Za-z0-9\-:\._]+)((?:\s+(?:[^"\'>]|"[^"]*"|\'[^\']*\')*)?)>(.*?)<\/x-\1>/s',
                function (array $matches) use ($parentId, $context, &$hasComponents): string {
                    $hasComponents = true;
                    return $this->renderComponentTag(
                        componentName: $matches[1],
                        attributes: $matches[2],
                        slotContent: $matches[3],
                        parentId: $parentId,
                        isSelfClosing: false,
                        context: $context
                    );
                },
                $html
            );

            // Then process self-closing tags: <x-name />
            $newHtml = preg_replace_callback(
                '/<x-([A-Za-z0-9\-:\._]+)((?:\s+(?:[^"\'\/>]|"[^"]*"|\'[^\']*\')*)?)\/>/s',
                function (array $matches) use ($parentId, $context, &$hasComponents): string {
                    $hasComponents = true;
                    return $this->renderComponentTag(
                        componentName: $matches[1],
                        attributes: $matches[2],
                        slotContent: '',
                        parentId: $parentId,
                        isSelfClosing: true,
                        context: $context
                    );
                },
                $newHtml
            );

            $html = $newHtml;
        } while ($hasComponents && $iteration < $iterationLimit);

        return $html;
    }

    /**
     * @return array<string, mixed>
     */
    private function parsePropsFromArray(string $arrayString, array $context): array
    {
        $trimmed = trim($arrayString);

        if ($trimmed === '') {
            return [];
        }

        try {
            $evaluated = $this->evaluatePhpExpression($trimmed, $context);

            if (is_array($evaluated)) {
                return $evaluated;
            }
        } catch (\Throwable $e) {
            // Silent fallback to empty props when evaluation fails
        }

        return [];
    }

    private function renderComponentTag(
        string $componentName,
        string $attributes,
        string $slotContent,
        string $parentId,
        bool $isSelfClosing,
        array $context
    ): string {
        $candidates = $this->buildComponentNameCandidates($componentName);
        $normalizedName = null;
        $componentContent = null;

        foreach ($candidates as $candidate) {
            $content = $this->componentService->loadComponent($candidate);

            if ($content !== null) {
                $normalizedName = $candidate;
                $componentContent = $content;
                break;
            }
        }

        $discovered = [];

        if ($componentContent === null) {
            $discovered = $this->componentService->discoverComponents();

            foreach ($candidates as $candidate) {
                $content = $this->componentService->loadComponent($candidate);

                if ($content !== null) {
                    $normalizedName = $candidate;
                    $componentContent = $content;
                    break;
                }
            }
        }

        if ($componentContent === null || $normalizedName === null) {
            $requested = implode("', '", $candidates);
            $availableComponents = $discovered === [] ? 'none' : implode(', ', array_keys($discovered));

            return "<!-- Component not found. Tried: ['{$requested}'] | Available: [{$availableComponents}] | Path: " . ink_resource_path('components') . " -->";
        }

        $componentId = $this->componentService->generateComponentId($normalizedName, $parentId);
        $parsedAttrs = $this->componentService->parseComponentAttributes($attributes);
        $preparedProps = $this->prepareComponentProps($parsedAttrs['props'], $context);
        $componentContext = $this->mergeComponentContext($context, $preparedProps);

        if (!$isSelfClosing && trim($slotContent) !== '') {
            $componentContent = str_replace(['{{ $slot }}', '{!! $slot !!}'], $slotContent, $componentContent);
        }

        // Recursively process nested components
        $componentContent = $this->processComponents($componentContent, $componentId, $componentContext);
        $componentContent = $this->translationTransformer->transform($componentContent);
        $componentContent = $this->bladeCompiler->compile($componentContent);

        return $this->componentService->compileComponent(
            $componentContent,
            $preparedProps,
            $componentId,
            $parsedAttrs['lazy']
        );
    }

    /**
     * @return array<int, string>
     */
    private function buildComponentNameCandidates(string $componentName): array
    {
        $candidates = [$componentName];
        $namespaceNormalized = str_replace('::', '.', $componentName);

        if ($namespaceNormalized !== $componentName) {
            $candidates[] = $namespaceNormalized;
        }

        $hyphenAsDot = str_replace('-', '.', $namespaceNormalized);

        if ($hyphenAsDot !== $namespaceNormalized) {
            $candidates[] = $hyphenAsDot;
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @param array<string, array{type: string, value: mixed}> $props
     * @return array<string, mixed>
     */
    private function prepareComponentProps(array $props, array $context): array
    {
        $prepared = [];

        foreach ($props as $name => $config) {
            switch ($config['type']) {
                case 'binding':
                    $prepared[$name] = $this->evaluateBindingValue($config['value'], $context);
                    break;
                case 'boolean':
                    $prepared[$name] = (bool) $config['value'];
                    break;
                default:
                    $prepared[$name] = $config['value'];
            }
        }

        return $prepared;
    }

    /**
     * @param array<string, mixed> $baseContext
     * @param array<string, mixed> $props
     * @return array<string, mixed>
     */
    private function mergeComponentContext(array $baseContext, array $props): array
    {
        $context = $baseContext;

        foreach ($props as $name => $value) {
            if (preg_match('/^[A-Za-z_][\w]*$/', $name)) {
                $context[$name] = $value;
            }
        }

        return $context;
    }

    private function evaluateBindingValue(string $expression, array $context): mixed
    {
        try {
            return $this->evaluatePhpExpression($expression, $context);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function evaluatePhpExpression(string $expression, array $context): mixed
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ink');

        if ($tempFile === false) {
            throw new \RuntimeException('Unable to create temporary file for expression evaluation.');
        }

        $code = "<?php\nreturn (function() {\n";

        foreach ($context as $name => $value) {
            $code .= sprintf('$%s = %s;' . "\n", $name, var_export($value, true));
        }

        $code .= sprintf("return %s;\n", $expression);
        $code .= "})();";

        try {
            if (file_put_contents($tempFile, $code) === false) {
                throw new \RuntimeException('Failed to write temporary evaluation file.');
            }

            $result = include $tempFile;
        } finally {
            @unlink($tempFile);
        }

        return $result;
    }
}
